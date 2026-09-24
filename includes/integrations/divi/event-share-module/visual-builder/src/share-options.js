(function (root, factory) {
	const api = factory();
	if (typeof module === 'object' && module.exports) module.exports = api;
	if (root) root.wpSeedEventsShareOptions = api;
})(typeof window === 'object' ? window : null, function () {
	const toPlainObject = (value) => (value && typeof value.toJS === 'function' ? value.toJS() : value);
	const getAttribute = (attrs, key) => toPlainObject(
		attrs && typeof attrs.get === 'function' ? attrs.get(key) : attrs?.[key],
	);

	const getContentValues = (attrs) => (
		getAttribute(attrs, 'content')?.innerContent?.desktop?.value ?? {}
	);

	const getButtonIcon = (attrs, attrName) => {
		const icon = getAttribute(attrs, attrName)?.decoration?.button?.desktop?.value?.icon;

		return icon?.enable === 'on' && typeof icon?.settings?.unicode === 'string'
			? icon.settings.unicode
			: '';
	};

	const getButtonIconSetting = (attrs, attrName, key, fallback) => {
		const icon = getAttribute(attrs, attrName)?.decoration?.button?.desktop?.value?.icon;
		const value = icon?.[key];

		return typeof value === 'string' && value ? value : fallback;
	};

	const normalizeOptions = (attrs) => {
		const values = getContentValues(attrs);
		const text = (value, fallback) => (typeof value === 'string' && value.trim() ? value : fallback);
		const actionOrders = [
			'share_copy_email',
			'share_email_copy',
			'copy_share_email',
			'copy_email_share',
			'email_share_copy',
			'email_copy_share',
		];

		return {
			display_mode: ['text_icon', 'text', 'icon'].includes(values.display_mode) ? values.display_mode : 'text_icon',
			action_order: actionOrders.includes(values.action_order) ? values.action_order : 'share_copy_email',
			label: text(values.label, 'Partager'),
			show_share: typeof values.show_share === 'string' ? values.show_share : 'on',
			share_icon: getButtonIcon(attrs, 'shareActionStyle'),
			share_icon_placement: getButtonIconSetting(attrs, 'shareActionStyle', 'placement', 'right'),
			share_icon_on_hover: getButtonIconSetting(attrs, 'shareActionStyle', 'onHover', 'on'),
			show_copy: typeof values.show_copy === 'string' ? values.show_copy : 'on',
			copy_label: text(values.copy_label, 'Copier le lien'),
			copy_icon: getButtonIcon(attrs, 'copyActionStyle'),
			copy_icon_placement: getButtonIconSetting(attrs, 'copyActionStyle', 'placement', 'right'),
			copy_icon_on_hover: getButtonIconSetting(attrs, 'copyActionStyle', 'onHover', 'on'),
			show_email: typeof values.show_email === 'string' ? values.show_email : 'on',
			email_label: text(values.email_label, 'Par email'),
			email_icon: getButtonIcon(attrs, 'emailActionStyle'),
			email_icon_placement: getButtonIconSetting(attrs, 'emailActionStyle', 'placement', 'right'),
			email_icon_on_hover: getButtonIconSetting(attrs, 'emailActionStyle', 'onHover', 'on'),
		};
	};

	return { getAttribute, getButtonIcon, getButtonIconSetting, getContentValues, normalizeOptions, toPlainObject };
});
