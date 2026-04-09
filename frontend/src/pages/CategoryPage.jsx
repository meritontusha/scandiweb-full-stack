import ProductCard from '../components/ProductCard';

function CategoryPage({ categoryName, products, onQuickAdd }) {
  return (
    <main className="page">
      <h1>{categoryName}</h1>
      <section className="grid">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} onQuickAdd={onQuickAdd} />
        ))}
      </section>
    </main>
  );
}

export default CategoryPage;
