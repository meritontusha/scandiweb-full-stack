import { create } from 'zustand';
import { apolloClient } from '../api/apolloClient';
import { PLACE_ORDER } from '../api/graphql';
import { cartItemKey } from '../utils/format';

const STORAGE_KEY = 'scandiweb-cart';

const loadState = () => {
  if (typeof window === 'undefined') {
    return [];
  }

  try {
    const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    return parsed.items || [];
  } catch {
    return [];
  }
};

const persist = (items) => {
  if (typeof window !== 'undefined') {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ items }));
  }
};

export const useCartStore = create((set, get) => ({
  items: loadState(),
  isOverlayOpen: false,
  addToCart: (product, selectedAttributes) => {
    const key = cartItemKey(product.id, selectedAttributes);
    const items = [...get().items];
    const idx = items.findIndex((item) => item.key === key);

    if (idx > -1) {
      items[idx].quantity += 1;
    } else {
      items.push({ key, product, quantity: 1, selectedAttributes });
    }

    persist(items);
    set({ items, isOverlayOpen: true });
  },
  increaseItem: (key) => {
    const items = get().items.map((item) => (item.key === key ? { ...item, quantity: item.quantity + 1 } : item));
    persist(items);
    set({ items });
  },
  decreaseItem: (key) => {
    const items = get().items
      .map((item) => (item.key === key ? { ...item, quantity: item.quantity - 1 } : item))
      .filter((item) => item.quantity > 0);
    persist(items);
    set({ items });
  },
  setOverlayOpen: (isOverlayOpen) => set({ isOverlayOpen }),
  placeOrder: async () => {
    const items = get().items;
    if (items.length === 0) return null;

    const payload = items.map((item) => ({
      productId: item.product.id,
      quantity: item.quantity,
      selectedAttributes: Object.values(item.selectedAttributes).map((attr) => ({
        attributeId: attr.attributeId,
        value: attr.value,
      })),
    }));

    const result = await apolloClient.mutate({
      mutation: PLACE_ORDER,
      variables: { items: payload },
    });

    persist([]);
    set({ items: [], isOverlayOpen: false });
    return result.data?.placeOrder ?? null;
  },
}));
