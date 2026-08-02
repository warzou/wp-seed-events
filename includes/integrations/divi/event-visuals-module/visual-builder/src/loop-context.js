const getDiviAttribute = (attrs, key) => {
  if (attrs && typeof attrs.get === 'function') {
    return attrs.get(key);
  }

  return attrs?.[key];
};

const getDiviLoopPostId = (attrs) => {
  const value = Number(getDiviAttribute(attrs, '__loop_post_id') ?? 0);

  return Number.isSafeInteger(value) && value > 0 ? value : 0;
};

module.exports = {
  getDiviAttribute,
  getDiviLoopPostId,
};