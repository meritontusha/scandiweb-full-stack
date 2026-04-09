import { Link, useLocation } from 'react-router-dom';
import { useCartStore } from '../store/cartStore';

function Header({ categories }) {
  const location = useLocation();
  const items = useCartStore((state) => state.items);
  const isOverlayOpen = useCartStore((state) => state.isOverlayOpen);
  const setOverlayOpen = useCartStore((state) => state.setOverlayOpen);
  const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <header className="header">
      <nav className="category-nav">
        {categories.map((category) => {
          const path = `/${category.name}`;
          const isActive = location.pathname === path;
          return (
            <Link
              key={category.name}
              to={path}
              className={isActive ? 'active' : ''}
              data-testid={isActive ? 'active-category-link' : 'category-link'}
            >
              {category.name}
            </Link>
          );
        })}
      </nav>

      <div className="brand">SCANDIWEB</div>

      <button
        type="button"
        className="cart-btn"
        data-testid="cart-btn"
        onClick={() => setOverlayOpen(!isOverlayOpen)}
      >
        🛒
        {totalItems > 0 && <span className="cart-badge">{totalItems}</span>}
      </button>
    </header>
  );
}

export default Header;
