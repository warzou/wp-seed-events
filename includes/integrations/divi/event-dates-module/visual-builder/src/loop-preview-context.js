const context = require('../../../visual-builder-event-context');

module.exports = {
  ...context,
  createEventDatesPreviewFilter: ({ React, data }) => context.createEventModulePreviewFilter({
    React,
    data,
    moduleNames: ['wp-seed-events/event-dates'],
  }),
};
