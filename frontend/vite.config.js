import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
      '/sanctum': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
      '/uploads': {
        target: 'https://mlmbookai.com',
        changeOrigin: true,
      },
      '/logo': {
        target: 'https://mlmbookai.com',
        changeOrigin: true,
      },
      '/f_assets': {
        target: 'https://mlmbookai.com',
        changeOrigin: true,
      },
      '/member_assets': {
        target: 'https://mlmbookai.com',
        changeOrigin: true,
      },
      '/storage': {
        target: 'https://mlmbookai.com',
        changeOrigin: true,
      },
    },
  },
});
