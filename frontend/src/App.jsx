import { useQuery } from '@apollo/client/react';
import { Navigate, Route, Routes, useParams } from 'react-router-dom';
import Header from './components/Header';
import CartOverlay from './components/CartOverlay';
import CategoryPage from './pages/CategoryPage';
import ProductPage from './pages/ProductPage';
import { GET_CATEGORIES, GET_PRODUCT, GET_PRODUCTS } from './api/graphql';
import { useCartStore } from './store/cartStore';

function LoadingState() {
  return <main className="page">Loading...</main>;
}

function ErrorState() {
  return <main className="page">Something went wrong while loading data.</main>;
}

function CategoryRoute({ onQuickAdd }) {
  const { name } = useParams();
  const { data, loading, error } = useQuery(GET_PRODUCTS, {
    variables: { category: name === 'all' ? 'all' : name ?? 'all' },
  });

  if (loading) return <LoadingState />;
  if (error) return <ErrorState />;

  return <CategoryPage categoryName={name || 'all'} products={data?.products ?? []} onQuickAdd={onQuickAdd} />;
}

function ProductRoute({ onAddToCart }) {
  const { id } = useParams();
  const { data, loading, error } = useQuery(GET_PRODUCT, {
    variables: { id },
    skip: !id,
  });

  if (loading) return <LoadingState />;
  if (error) return <ErrorState />;
  if (!data?.product) return <main className="page">Product not found.</main>;

  return <ProductPage key={data.product.id} product={data.product} onAddToCart={onAddToCart} />;
}

function App() {
  const { data, loading, error } = useQuery(GET_CATEGORIES);
  const categories = data?.categories ?? [];

  const isOverlayOpen = useCartStore((state) => state.isOverlayOpen);
  const addToCart = useCartStore((state) => state.addToCart);

  const quickAdd = (product) => {
    const defaults = Object.fromEntries(
      product.attributes
        .filter((attribute) => attribute.items.length > 0)
        .map((attribute) => [
          attribute.id,
          {
            id: attribute.items[0].id,
            attributeId: attribute.id,
            attributeName: attribute.name,
            displayValue: attribute.items[0].displayValue,
            value: attribute.items[0].value,
          },
        ])
    );

    addToCart(product, defaults);
  };

  if (loading) return <LoadingState />;
  if (error) return <ErrorState />;

  return (
    <div>
      <Header categories={categories} />
      {isOverlayOpen && <div className="page-overlay" />}
      {isOverlayOpen && <CartOverlay />}

      <Routes>
        <Route path="/" element={<Navigate to="/all" replace />} />
        <Route path="/:name" element={<CategoryRoute onQuickAdd={quickAdd} />} />
        <Route path="/product/:id" element={<ProductRoute onAddToCart={addToCart} />} />
      </Routes>
    </div>
  );
}

export default App;
