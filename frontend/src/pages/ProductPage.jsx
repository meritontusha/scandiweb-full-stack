import { useMemo, useState } from 'react';
import parse from 'html-react-parser';
import { formatPrice, toAttributeOptionTestIdValue,toKebabCase } from '../utils/format';

function ProductPage({ product, onAddToCart }) {
  const [selectedImage, setSelectedImage] = useState(0);
  const [selectedAttributes, setSelectedAttributes] = useState({});

  const allRequiredSelected = useMemo(
    () => product.attributes.every((attribute) => selectedAttributes[attribute.id]),
    [product.attributes, selectedAttributes]
  );

  const canAdd = product.inStock && allRequiredSelected;
  const nextImage = () => setSelectedImage((prev) => (prev + 1) % product.gallery.length);
  const prevImage = () => setSelectedImage((prev) => (prev - 1 + product.gallery.length) % product.gallery.length);

  const selectAttribute = (attribute, item) => {
    setSelectedAttributes((prev) => ({
      ...prev,
      [attribute.id]: {
        id: item.id,
        attributeId: attribute.id,
        attributeName: attribute.name,
        displayValue: item.displayValue,
        value: item.value,
      },
    }));
  };

  return (
    <main className="page product-page">
      <div className="gallery-list" data-testid="product-gallery">
        {product.gallery.map((image, idx) => (
          <button key={image} type="button" onClick={() => setSelectedImage(idx)}>
            <img src={image} alt={`${product.name}-${idx}`} />
          </button>
        ))}
      </div>

      <div className="main-image-wrap">
        {product.gallery.length > 1 && (
          <>
            <button type="button" className="gallery-arrow left" onClick={prevImage}>
              ‹
            </button>
            <button type="button" className="gallery-arrow right" onClick={nextImage}>
              ›
            </button>
          </>
        )}
        <img src={product.gallery[selectedImage]} alt={product.name} className="main-image" />
      </div>

      <section className="product-details">
        <h2>{product.brand}</h2>
        <h3>{product.name}</h3>

        {product.attributes.map((attribute) => {
          const attrName = toKebabCase(attribute.name);
          return (
            <div key={attribute.name} data-testid={`product-attribute-${attrName}`}>
              <h4>{attribute.name}:</h4>
              <div className="attribute-items">
                {attribute.items.map((item) => {
                  const isSelected = selectedAttributes[attribute.id]?.id === item.id;
                  const optionName = toAttributeOptionTestIdValue(item);
                  return (
                    <button
                      key={item.id}
                      type="button"
                      className={`attr-btn ${isSelected ? 'selected' : ''} ${attribute.type === 'swatch' ? 'swatch' : ''}`}
                      style={attribute.type === 'swatch' ? { backgroundColor: item.value } : undefined}
                      data-testid={`product-attribute-${attrName}-${optionName}`}
                      onClick={() => selectAttribute(attribute, item)}
                    >
                      {attribute.type === 'swatch' ? '' : item.displayValue}
                    </button>
                  );
                })}
              </div>
            </div>
          );
        })}

        <h4>PRICE:</h4>
        <p>{formatPrice(product.prices[0])}</p>

        <button type="button" data-testid="add-to-cart" className="add-to-cart" disabled={!canAdd} onClick={() => onAddToCart(product, selectedAttributes)}>
          ADD TO CART
        </button>

        <div data-testid="product-description" className="product-description">
          {parse(product.description)}
        </div>
      </section>
    </main>
  );
}

export default ProductPage;
