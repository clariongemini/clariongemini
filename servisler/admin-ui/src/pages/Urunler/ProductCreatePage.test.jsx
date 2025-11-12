import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import ProductCreatePage from './ProductCreatePage';
import apiClient from '../../services/apiClient';

// apiClient'ı mock'la
vi.mock('../../services/apiClient');

describe('ProductCreatePage', () => {
  it('submits the form with the correct data', async () => {
    // 1. Arrange
    const user = userEvent.setup();
    const handleSave = vi.fn();
    // apiClient.post'un başarılı bir yanıt döndürdüğünü simüle et
    apiClient.post.mockResolvedValue({ data: { yeni_urun_id: 123 } });
    render(<ProductCreatePage onSave={handleSave} />);

    const input = screen.getByLabelText(/ürün adı/i);
    const saveButton = screen.getByRole('button', { name: /kaydet/i });

    // 2. Act
    await user.type(input, 'Yeni Laptop');
    await user.click(saveButton);

    // 3. Assert
    // apiClient.post'un doğru argümanlarla çağrıldığını kontrol et
    expect(apiClient.post).toHaveBeenCalledTimes(1);
    expect(apiClient.post).toHaveBeenCalledWith('/api/admin/urunler', { urun_adi: 'Yeni Laptop' });

    // onSave callback'inin API yanıtıyla çağrıldığını kontrol et
    expect(handleSave).toHaveBeenCalledWith({ yeni_urun_id: 123 });
  });
});
