import { formatPrice, toAttributeOptionTestIdValue,toKebabCase } from '../utils/format';
import { useCartStore } from '../store/cartStore';

function CartOverlay() {
  const items = useCartStore((state) => state.items);
  const increaseItem = useCartStore((state) => state.increaseItem);
  const decreaseItem = useCartStore((state) => state.decreaseItem);
  const placeOrder = useCartStore((state) => state.placeOrder);
  const setOverlayOpen = useCartStore((state) => state.setOverlayOpen);
  const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
  const totalPrice = items.reduce((sum, item) => {
    const price = item.product.prices[0]?.amount ?? 0;
    return sum + price * item.quantity;
  }, 0);
  const totalCurrency = items[0]?.product.prices[0]?.currency ?? { label: 'USD', symbol: '$' };

  const onOrder = async () => {
    await placeOrder();
  };

  return (
    <aside className="cart-overlay" data-testid="cart-overlay">
      <h2>
        My Bag, {totalItems} {totalItems === 1 ? 'Item' : 'Items'}
      </h2>

      <div className="cart-items">
        {items.map((item) => (
          <article key={item.key} className="cart-item">
            <div className="cart-info">
              <p>{item.product.brand}</p>
              <p>{item.product.name}</p>
              <p>{formatPrice(item.product.prices[0])}</p>

              {item.product.attributes.map((attribute) => {
                const selected = item.selectedAttributes[attribute.id];
                const attrName = toKebabCase(attribute.name);

                return (
                  <div key={attribute.name} data-testid={`cart-item-attribute-${attrName}`}>
                    <small>{attribute.name}:</small>
                    <div className="attribute-items">
                      {attribute.items.map((option) => {
                        const selectedClass = selected?.id === option.id ? 'selected' : '';
                        const optionName = toAttributeOptionTestIdValue(item);
                        const testBase = `cart-item-attribute-${attrName}-${optionName}`;
                        const testId = selectedClass ? `${testBase}-selected` : testBase;

                        return (
                          <button
                            key={option.id}
                            type="button"
                            className={`attr-btn ${selectedClass} ${attribute.type === 'swatch' ? 'swatch' : ''}`}
                            style={attribute.type === 'swatch' ? { backgroundColor: option.value } : undefined}
                            data-testid={testId}
                            disabled
                          >
                            {attribute.type === 'swatch' ? '' : option.displayValue}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                );
              })}
            </div>

            <div className="cart-qty">
              <button type="button" data-testid="cart-item-amount-increase" onClick={() => increaseItem(item.key)}>
                +
              </button>
              <span data-testid="cart-item-amount">{item.quantity}</span>
              <button type="button" data-testid="cart-item-amount-decrease" onClick={() => decreaseItem(item.key)}>
                -
              </button>
            </div>

            <img src={item.product.gallery[0]} alt={item.product.name} className="cart-thumb" />
          </article>
        ))}
      </div>

      <div className="cart-total-row">
        <span>Total</span>
        <strong data-testid="cart-total">{formatPrice({ amount: totalPrice, currency: totalCurrency })}</strong>
      </div>

      <div className="cart-actions">
        <button type="button" onClick={() => setOverlayOpen(false)}>
          VIEW BAG
        </button>
        <button type="button" className="primary" disabled={items.length === 0} onClick={onOrder}>
          PLACE ORDER
        </button>
      </div>
    </aside>
  );
}

export default CartOverlay;
