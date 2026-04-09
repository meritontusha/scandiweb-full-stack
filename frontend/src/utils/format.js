export const toKebabCase = (value) =>
  value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)+/g, '');

export const toAttributeOptionTestIdValue = (item) =>
  String(item?.displayValue ?? item?.value ?? item?.id ?? '');

export const formatPrice = (price) => {
  if (!price) {
    return '$0.00';
  }

  return `${price.currency.symbol}${Number(price.amount).toFixed(2)}`;
};

export const cartItemKey = (productId, selectedAttributes) => {
  const attrs = Object.entries(selectedAttributes)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([name, item]) => `${name}:${item.id}`)
    .join('|');
  return `${productId}::${attrs}`;
};
