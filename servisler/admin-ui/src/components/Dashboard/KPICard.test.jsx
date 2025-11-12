import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import KPICard from './KPICard';

describe('KPICard', () => {
  it('renders title and value from props', () => {
    // 1. Arrange
    const title = "Toplam Sipariş";
    const value = "1,500";
    render(<KPICard title={title} value={value} />);

    // 2. Assert
    // Başlığın ve değerin ekranda olup olmadığını kontrol et
    expect(screen.getByText("Toplam Sipariş")).toBeInTheDocument();
    expect(screen.getByText("1,500")).toBeInTheDocument();
  });
});
