(function (window) {
    'use strict';

    var vendor = window.vendor || {};
    var hooks = vendor.wp && vendor.wp.hooks;
    var React = vendor.React;
    var data = window.WpSeedEventsDiviEventResultsConditionData || {};
    var conditionName = 'wpSeedEventsHasResults';
    var label = 'WP Seed Events — Événements disponibles';
    var eventTypes = Array.isArray(data.eventTypes) ? data.eventTypes : [];

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

    function Settings(props) {
        var settings = props.item.conditionSettings || {};
        var selectedTypes = Array.isArray(settings.eventTypes) ? settings.eventTypes.map(String) : [];

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
            ))
        );
    }

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.conditionsStore',
        'wp-seed-events/event-results/conditions-store',
        function (conditions) {
            var exists = conditions.some(function (condition) { return condition.name === conditionName; });
            return exists ? conditions : conditions.concat([{
                name: conditionName,
                label: label,
                category: 'postInfo'
            }]);
        }
    );

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.initialCustomItemEdit',
        'wp-seed-events/event-results/initial-item',
        function (item, selectedName, id, operator) {
            if (conditionName !== selectedName) {
                return item;
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
                    eventPinned: 'all'
                },
                operator: operator
            };
        }
    );

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.customSettingsComponent',
        'wp-seed-events/event-results/settings',
        function (component, item, setItem) {
            if (!item || conditionName !== item.conditionName) {
                return component;
            }
            return React.createElement(Settings, { item: item, setItem: setItem });
        }
    );

    hooks.addFilter(
        'divi.fieldLibrary.conditionalDisplay.tooltips.customTooltip',
        'wp-seed-events/event-results/tooltip',
        function (tooltip, selectedName) {
            return conditionName === selectedName ? 'Afficher si au moins un événement correspond.' : tooltip;
        }
    );
}(window));
