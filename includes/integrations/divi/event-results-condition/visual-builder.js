(function (window) {
    'use strict';

    var vendor = window.vendor || {};
    var hooks = vendor.wp && vendor.wp.hooks;
    var React = vendor.React;
    var data = window.WpSeedEventsDiviEventResultsConditionData || {};
    var conditionName = 'wpSeedEventsHasResults';
    var label = 'WPSEvents — Événements disponibles';
    var currentTypeConditionName = 'wpSeedEventsCurrentEventHasType';
    var currentTypeLabel = 'WPSEvents — Type de l’événement courant';
    var eventTypes = Array.isArray(data.eventTypes) ? data.eventTypes : [];
    var currentEventTypes = Array.isArray(data.currentEventTypes) ? data.currentEventTypes : [];

    if (!hooks || !React) {
        return;
    }

    function updateSetting(setItem, key, value) {
        setItem(function (item) {
            var nextSettings = Object.assign({}, item.conditionSettings || {});
            nextSettings[key] = value;
            return Object.assign({}, item, { conditionSettings: nextSettings });
        });
    }

    function field(labelText, control) {
        return React.createElement(
            'label',
            { style: { display: 'block', marginBottom: '14px' } },
            React.createElement('span', { style: { display: 'block', fontWeight: 600, marginBottom: '6px' } }, labelText),
            control
        );
    }

    function ResultsSettings(props) {
        var settings = props.item.conditionSettings || {};
        var selectedTypes = Array.isArray(settings.eventTypes) ? settings.eventTypes.map(String) : [];
        var resultCount = Number.parseInt(settings.resultCount, 10);
        resultCount = Number.isFinite(resultCount) ? Math.max(0, resultCount) : 1;

        return React.createElement(
            'div',
            null,
            field('Statut temporel', React.createElement(
                'select',
                {
                    value: settings.eventStatus || 'upcoming',
                    onChange: function (event) { updateSetting(props.setItem, 'eventStatus', event.target.value); },
                    style: { width: '100%' }
                },
                React.createElement('option', { value: 'upcoming' }, 'À venir'),
                React.createElement('option', { value: 'to_schedule' }, 'À programmer'),
                React.createElement('option', { value: 'past' }, 'Passés'),
                React.createElement('option', { value: 'all' }, 'Tous')
            )),
            field('Types d’événement', React.createElement(
                'div',
                null,
                eventTypes.length === 0
                    ? React.createElement('span', null, 'Tous les types')
                    : eventTypes.map(function (option) {
                        var value = String(option.value);
                        return React.createElement(
                            'label',
                            { key: value, style: { display: 'block', marginBottom: '6px' } },
                            React.createElement('input', {
                                type: 'checkbox',
                                checked: selectedTypes.indexOf(value) !== -1,
                                onChange: function (event) {
                                    var next = event.target.checked
                                        ? selectedTypes.concat([value])
                                        : selectedTypes.filter(function (selected) { return selected !== value; });
                                    updateSetting(props.setItem, 'eventTypes', next);
                                }
                            }),
                            ' ',
                            option.label
                        );
                    })
            )),
            field('Épinglage', React.createElement(
                'select',
                {
                    value: settings.eventPinned || 'all',
                    onChange: function (event) { updateSetting(props.setItem, 'eventPinned', event.target.value); },
                    style: { width: '100%' }
                },
                React.createElement('option', { value: 'all' }, 'Tous'),
                React.createElement('option', { value: 'featured_only' }, 'Uniquement les événements épinglés'),
                React.createElement('option', { value: 'exclude_featured' }, 'Exclure les événements épinglés')
            )),
            field('Nombre de résultats', React.createElement(
                'div',
                { style: { display: 'grid', gridTemplateColumns: 'minmax(0, 1fr) 88px', gap: '8px' } },
                React.createElement(
                    'select',
                    {
                        value: settings.resultCountOperator || 'at_least',
                        onChange: function (event) { updateSetting(props.setItem, 'resultCountOperator', event.target.value); }
                    },
                    React.createElement('option', { value: 'at_least' }, 'Au moins'),
                    React.createElement('option', { value: 'equals' }, 'Exactement'),
                    React.createElement('option', { value: 'greater_than' }, 'Plus de')
                ),
                React.createElement('input', {
                    type: 'number',
                    min: 0,
                    step: 1,
                    value: resultCount,
                    onChange: function (event) {
                        var next = Number.parseInt(event.target.value, 10);
                        updateSetting(props.setItem, 'resultCount', Number.isFinite(next) ? Math.max(0, next) : 0);
                    }
                })
            ))
        );
    }

    function CurrentEventTypeSettings(props) {
        var settings = props.item.conditionSettings || {};
        var selectedTypes = Array.isArray(settings.eventTypes) ? settings.eventTypes.map(String) : [];

        return field('Types d’événement', React.createElement(
            'div',
            null,
            currentEventTypes.length === 0
                ? React.createElement('span', null, 'Aucun type actif')
                : currentEventTypes.map(function (option) {
                    var value = String(option.value);
                    return React.createElement(
                        'label',
                        { key: value, style: { display: 'block', marginBottom: '6px' } },
                        React.createElement('input', {
                            type: 'checkbox',
                            checked: selectedTypes.indexOf(value) !== -1,
                            onChange: function (event) {
                                var next = event.target.checked
                                    ? selectedTypes.concat([value])
                                    : selectedTypes.filter(function (selected) { return selected !== value; });
                                updateSetting(props.setItem, 'eventTypes', next);
                            }
                        }),
                        ' ',
                        option.label
                    );
                })
        ));
    }

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.conditionsStore',
        'wp-seed-events/event-results/conditions-store',
        function (conditions) {
            var next = conditions.slice();

            if (!next.some(function (condition) { return condition.name === conditionName; })) {
                next.push({ name: conditionName, label: label, category: 'postInfo' });
            }

            if (!next.some(function (condition) { return condition.name === currentTypeConditionName; })) {
                next.push({ name: currentTypeConditionName, label: currentTypeLabel, category: 'postInfo' });
            }

            return next;
        }
    );

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.initialCustomItemEdit',
        'wp-seed-events/event-results/initial-item',
        function (item, selectedName, id, operator) {
            if (conditionName !== selectedName && currentTypeConditionName !== selectedName) {
                return item;
            }

            if (currentTypeConditionName === selectedName) {
                return {
                    id: id,
                    conditionName: currentTypeConditionName,
                    conditionSettings: {
                        displayRule: 'is',
                        enableCondition: 'on',
                        adminLabel: currentTypeLabel,
                        eventTypes: []
                    },
                    operator: operator
                };
            }

            return {
                id: id,
                conditionName: conditionName,
                conditionSettings: {
                    displayRule: 'is',
                    enableCondition: 'on',
                    adminLabel: label,
                    eventStatus: 'upcoming',
                    eventTypes: [],
                    eventPinned: 'all',
                    resultCountOperator: 'at_least',
                    resultCount: 1
                },
                operator: operator
            };
        }
    );

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.customSettingsComponent',
        'wp-seed-events/event-results/settings',
        function (component, item, setItem) {
            if (!item) {
                return component;
            }

            if (conditionName === item.conditionName) {
                return React.createElement(ResultsSettings, { item: item, setItem: setItem });
            }

            if (currentTypeConditionName === item.conditionName) {
                return React.createElement(CurrentEventTypeSettings, { item: item, setItem: setItem });
            }

            return component;
        }
    );

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.tooltips.customTooltip',
        'wp-seed-events/event-results/tooltip',
        function (tooltip, selectedName) {
            if (conditionName === selectedName) {
                return 'Afficher selon le nombre d’événements correspondant à la collection.';
            }

            return currentTypeConditionName === selectedName
                ? 'Afficher lorsque l’événement courant possède au moins un des types sélectionnés.'
                : tooltip;
        }
    );
}(window));
