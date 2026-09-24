const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const diviRoot = path.join(root, 'includes/integrations/divi');
const modules = {
  dates: {
    metadata: 'event-dates-module/visual-builder/src/module.json',
    source: 'event-dates-module/visual-builder/src/index.jsx',
    php: 'class-event-dates-module.php',
  },
  people: {
    metadata: 'event-people-module/visual-builder/src/module.json',
    source: 'event-people-module/visual-builder/src/index.jsx',
    php: 'class-event-people-module.php',
  },
  visuals: {
    metadata: 'event-visuals-module/visual-builder/src/module.json',
    source: 'event-visuals-module/visual-builder/src/index.jsx',
    php: 'class-event-visuals-module.php',
  },
  share: {
    metadata: 'event-share-module/visual-builder/src/module.json',
  },
  occurrences: {
    metadata: 'occurrence-collection-module/visual-builder/src/module.json',
  },
};

const targets = {
  dates: ['Module', 'Date', 'Horaire', 'État annulé', 'Séparateur Date / Horaire', 'Liste'],
  people: ['Module', 'Personne', 'Nom', 'Coordonnées', 'Liste', 'Séparateur Nom / Coordonnées'],
  visuals: ['Module', 'Élément', 'Image', 'Légende'],
};

const proposals = {
  share: ['Module', 'Bouton Partager', 'Copier le lien', 'Email'],
  occurrences: ['Collection', 'Promotion', 'Année', 'Thème', 'Occurrence', 'Titres', 'Libellés', 'Valeurs', 'État vide', 'Pagination'],
};

const read = (relative) => fs.readFileSync(path.join(diviRoot, relative), 'utf8');
const readMetadata = (relative) => JSON.parse(read(relative));

const visibleStyleGroups = (metadata) => {
  const groups = [];
  Object.entries(metadata.attributes ?? {}).forEach(([attributeName, attribute]) => {
    Object.entries(attribute?.settings?.decoration ?? {}).forEach(([feature, settings]) => {
      if (settings?.render === false) return;
      groups.push(settings?.component?.props?.groupLabel || `${attributeName}:${feature}`);
    });
  });
  Object.values(metadata.settings?.groups ?? {}).forEach((group) => {
    if (group?.panel === 'design') groups.push(group?.component?.props?.groupLabel || group.groupName);
  });
  return groups;
};

const inventory = Object.fromEntries(Object.entries(modules).map(([key, config]) => {
  const metadata = readMetadata(config.metadata);
  return [key, { metadata, groups: visibleStyleGroups(metadata) }];
}));

Object.entries(targets).forEach(([module, expected]) => {
  assert.deepStrictEqual(inventory[module].groups, expected, `${module}: unexpected new-instance Style groups`);
  assert.ok(inventory[module].groups.length <= 7, `${module}: too many business-level Style groups`);
  assert.strictEqual(new Set(inventory[module].groups).size, inventory[module].groups.length, `${module}: ambiguous duplicate Style labels`);
  assert.ok(!inventory[module].groups.includes('Titre'), `${module}: legacy title Style remains exposed`);
  Object.entries(inventory[module].metadata.attributes).forEach(([attributeName, attribute]) => {
    Object.entries(attribute?.settings?.decoration ?? {}).forEach(([feature, settings]) => {
      assert.notStrictEqual(
        settings?.render,
        false,
        `${module}: ${attributeName}.${feature} relies on render:false, which Divi 5.9 still registers`,
      );
    });
  });
});

const forbiddenGroupPattern = /^(?:Dimensions?|Dimensionnement|Espacement|Bordure|Ombre|Boîte ombre)(?:\s|$)|^(?:Section|Figure|Lien image)$/i;
['dates', 'people', 'visuals'].forEach((module) => {
  assert.ok(
    !inventory[module].groups.some((group) => forbiddenGroupPattern.test(group)),
    `${module}: DOM-internal or generic builder-owned group remains exposed`,
  );
});

const legacyAttributes = {
  dates: [
    'module', 'sectionStyle', 'titleStyle', 'listStyle', 'dateStyle', 'timeStyle', 'separatorStyle',
    'calendarLinkStyle', 'occurrenceCalendarStyle', 'allCalendarStyle', 'statusStyle', 'occurrenceStyle',
  ],
  people: [
    'module', 'sectionStyle', 'titleStyle', 'listStyle', 'itemStyle', 'nameStyle', 'rolesStyle',
    'roleStyle', 'contactsStyle', 'emailLinkStyle', 'phoneLinkStyle', 'publicLinkStyle',
    'contactSeparatorStyle', 'nameContactSeparatorStyle', 'eventListStyle',
  ],
  visuals: [
    'module', 'sectionStyle', 'titleStyle', 'listStyle', 'gridStyle', 'listLayoutStyle', 'itemStyle',
    'figureStyle', 'imageStyle', 'captionStyle', 'documentStyle', 'imageLinkStyle', 'documentLinkStyle',
    'eventListStyle',
  ],
};

Object.entries(legacyAttributes).forEach(([module, attributes]) => {
  const metadata = inventory[module].metadata;
  const preview = read(modules[module].source);
  const php = read(modules[module].php);
  attributes.forEach((attribute) => {
    assert.ok(metadata.attributes[attribute], `${module}: historical attribute removed: ${attribute}`);
    const storageOnly = module === 'visuals'
      ? ['titleStyle', 'documentStyle', 'documentLinkStyle']
      : module === 'dates'
        ? ['eventListStyle', 'titleStyle', 'calendarLinkStyle', 'occurrenceCalendarStyle', 'allCalendarStyle']
        : ['eventListStyle', 'titleStyle'];
    if (!storageOnly.includes(attribute)) {
      assert.ok(preview.includes(`attrName: '${attribute}'`), `${module}: Visual Builder stopped rendering ${attribute}`);
      assert.ok(php.includes(`'${attribute}'`), `${module}: frontend stopped rendering ${attribute}`);
    } else if (module === 'dates' && attribute.includes('Calendar')) {
      assert.ok(!preview.includes(`attrName: '${attribute}'`), `${module}: Visual Builder still renders ${attribute}`);
      assert.ok(!php.includes(`'${attribute}'`), `${module}: frontend still renders ${attribute}`);
    }
  });
});

['dates', 'people', 'visuals'].forEach((module) => {
  assert.strictEqual(
    inventory[module].metadata.attributes.titleStyle.settings,
    undefined,
    `${module}: title Style declaration remains discoverable by the true Divi runtime`,
  );
  assert.ok(!read(modules[module].source).includes("attrName: 'titleStyle'"), `${module}: titleStyle still reaches Visual Builder`);
});

['occurrenceCalendarStyle', 'allCalendarStyle'].forEach((attribute) => {
  assert.strictEqual(inventory.dates.metadata.attributes[attribute].settings, undefined, `Dates: legacy ${attribute} is still exposed`);
  assert.ok(inventory.dates.metadata.attributes[attribute].default.decoration.button, `Dates: ${attribute} legacy defaults were removed`);
});

[
  'sectionStyle', 'listStyle', 'gridStyle', 'listLayoutStyle', 'figureStyle', 'documentStyle', 'imageLinkStyle',
].forEach((attribute) => {
  assert.strictEqual(inventory.visuals.metadata.attributes[attribute].settings, undefined, `Visuals: ${attribute} still exposes DOM internals`);
});

const historicalVisualsFixture = {
  sectionStyle: { decoration: { spacing: { desktop: { value: { padding: '12px' } } } } },
  imageStyle: { decoration: { filters: { desktop: { value: { saturate: '80%' } } } } },
  documentStyle: { decoration: { boxShadow: { desktop: { value: { color: '#123456' } } } } },
};
Object.keys(historicalVisualsFixture).forEach((attribute) => {
  assert.ok(inventory.visuals.metadata.attributes[attribute], `Visuals legacy fixture cannot register ${attribute}`);
});

Object.entries(proposals).forEach(([module, groups]) => {
  assert.ok(groups.length > 0, `${module}: no documented simplification target`);
});

console.log(JSON.stringify({
  visibleGroupCounts: Object.fromEntries(Object.entries(inventory).map(([key, value]) => [key, value.groups.length])),
  finalGroups: Object.fromEntries(Object.keys(targets).map((key) => [key, inventory[key].groups])),
  auditOnlyTargets: proposals,
}, null, 2));
console.log('Divi Style panel inventory: PASS');
