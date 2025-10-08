# Mackolik-Api-Match-Scraper
## Açıklama
Bu proje, Mackolik'in halka açık API'sini kullanarak belirli bir tarihteki futbol ve basketbol maçlarını listeleyen ve canlı olarak takip etme imkanı sunan bir web uygulamasıdır.
Bir hobi projesidir, vakit buldukça geliştirilecektir.

Ana fikir: https://github.com/EmreKara5aya/Php-Mackolik-Api

## 🚀 Özellikler

- **Canlı Skor ve Dakika Takibi:** Maç listesi ve detay sayfası, verileri periyodik olarak otomatik günceller.
- **Spor Dallarına Göre Sekmeler:** Maçlar, "Futbol" ve "Basketbol" olarak iki ayrı sekmede listelenir.
- **Akıllı Sıralama:** Canlı maçlar her zaman en üstte yer alır ve tüm maçlar kendi içinde başlama saatine göre sıralanır.
- **Favori Maçlar Sistemi:** Maçlar yıldızlanarak özel bir "Takip Ettiklerim" sekmesine eklenebilir. Favori futbol maçlarında gol olduğunda sesli ve görsel bildirim alınır.
- **Maç Detay Sayfası:** Her maç için özel detay*, canlı skor ve bildirim özellikleri.
- **Koyu Mod Desteği:** Tek tuşla açık ve koyu tema arasında geçiş.
- **Tarihler Arası Gezinti:** Önceki ve sonraki günün maçlarına kolayca erişim.

*Geliştirdikçe eklenecektir.

## 🛠️ Kurulum

1.  Bu projeyi çalıştırmak için XAMPP, WAMP gibi bir PHP ve Apache sunucusuna ihtiyacınız vardır.
2.  Tüm dosyaları sunucunuzun `htdocs` veya `www` klasörü içindeki bir dizine (örn: `mackolik`) kopyalayın.
3.  Gol sesi bildirimleri için proje ana dizinine `gol.mp3` adında bir ses dosyası ekleyin.
4.  Tarayıcınızdan `http://localhost/mackolik/canli.php` adresine gidin.

**Örnek Çıktı:**


Aramak istediğiniz maçları tarih (dd/mm/yyyy) ve kod adlı get değişkenlerle arayabilir ya da 'canli.php' üzerindeki menüyü kullanarak topluca favorilere ekleyebilir ya da iddaa koduna tıklayarak maçı anlık takip edebilirsiniz.
"0" kodlu maçlara İddaa tarafından bir kod verilmediği için API tarafından takibi mümkün olmamakta ve anlık güncellemeler yerine dakikalar/saatler içerisinde güncellemeler yapılmaktadır.

## ✍️ Geliştirici
- **github.com/awelmisin**

## Örnek

Örnek olarak 07/10/2025 tarihli ve 2436098 iddia kodlu Barcelona (K) - Bayern München (K) maçını arayalım.

**Örnek Link:** 

    siteadresi.com/maclar.php?kod=2436098&tarih=07/10/2025

**Örnek Çıktı:**


## Bağış


