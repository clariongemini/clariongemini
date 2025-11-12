import { http, HttpResponse } from 'msw';

export const handlers = [
  // Ürün oluşturma endpoint'ini taklit et
  http.post('/api/admin/urunler', async ({ request }) => {
    const urun = await request.json();
    // Testin başarılı olduğunu varsayarak basit bir başarı yanıtı dön
    return HttpResponse.json({ durum: 'basarili', yeni_urun_id: 123 }, { status: 201 });
  }),

  // Diğer endpoint'ler buraya eklenebilir...
];
