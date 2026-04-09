import { ApolloClient, HttpLink, InMemoryCache } from '@apollo/client';

const configuredApiUrl = import.meta.env.VITE_GRAPHQL_URL?.trim();
const API_URL = configuredApiUrl && configuredApiUrl.length > 0
  ? configuredApiUrl
  : '/graphql';

export const apolloClient = new ApolloClient({
  link: new HttpLink({
    uri: API_URL,
    headers: {
      'content-type': 'application/json',
    },
  }),
  cache: new InMemoryCache(),
});
