import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import TeslimAlModal from './TeslimAlModal';

describe('TeslimAlModal', () => {
  it('disables the save button when the entered amount exceeds the remaining amount', async () => {
    // 1. Arrange
    const user = userEvent.setup();
    render(<TeslimAlModal open={true} onClose={() => {}} kalanAdet={60} onSave={() => {}} />);

    const input = screen.getByLabelText(/teslim alınacak adet/i);
    const saveButton = screen.getByRole('button', { name: /kaydet/i });

    // Başlangıçta butonun devre dışı olduğunu doğrula
    expect(saveButton).toBeDisabled();

    // 2. Act: Kalan adetten daha büyük bir değer gir
    await user.type(input, '61');

    // 3. Assert: Butonun hala devre dışı olduğunu doğrula
    expect(saveButton).toBeDisabled();

    // Yardımcı metnin hata gösterdiğini doğrula (opsiyonel ama iyi bir pratik)
    expect(screen.getByText(/kalan adet: 60/i)).toBeInTheDocument();
  });

  it('enables the save button when a valid amount is entered', async () => {
    // 1. Arrange
    const user = userEvent.setup();
    render(<TeslimAlModal open={true} onClose={() => {}} kalanAdet={60} onSave={() => {}} />);

    const input = screen.getByLabelText(/teslim alınacak adet/i);
    const saveButton = screen.getByRole('button', { name: /kaydet/i });

    // 2. Act: Geçerli bir değer gir
    await user.type(input, '50');

    // 3. Assert: Butonun aktif olduğunu doğrula
    expect(saveButton).not.toBeDisabled();
  });
});
