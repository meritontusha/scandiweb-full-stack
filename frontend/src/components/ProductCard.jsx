import { Link } from 'react-router-dom';
import { toKebabCase, formatPrice } from '../utils/format';

function ProductCard({ product, onQuickAdd }) {
  const price = product.prices[0];

  return (
    <article className={`product-card ${product.inStock ? '' : 'out-of-stock'}`} data-testid={`product-${toKebabCase(product.name)}`}>
      <Link to={`/product/${product.id}`} className="product-link">
        <div className="product-image-wrap">
          <img src={product.gallery[0]} alt={product.name} className="product-image" />
          {!product.inStock && <div className="out-label">OUT OF STOCK</div>}
        </div>
        <h3>{product.name}</h3>
        <p>{formatPrice(price)}</p>
      </Link>

      {product.inStock && (
        <button className="quick-shop-btn" type="button" onClick={() => onQuickAdd(product)}>
          🛒
        </button>
      )}
    </article>
  );
}

export default ProductCard;
