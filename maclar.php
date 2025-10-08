<?php
if (!isset($_GET['kod']) || !isset($_GET['tarih'])) { die('<h1>Parametreler eksik.</h1>'); }
$kod = $_GET['kod'];
$tarih = $_GET['tarih'];

$veri = @file_get_contents("https://vd.mackolik.com/livedata?date=" . urlencode($tarih) . "&_=" . time());
$dizi = json_decode($veri, true);

if ($dizi === null || !isset($dizi['m'])) { die('Veri alınamadı veya format hatalı.'); }
$mac_index = array_search($kod, array_column($dizi['m'], 14));
if ($mac_index === false) { die('Belirtilen maça ait veri bulunamadı.'); }

$mac_detay = $dizi['m'][$mac_index];

$ev_sahibi_adi = htmlspecialchars($mac_detay[2]);
$deplasman_adi = htmlspecialchars($mac_detay[4]);
$ev_skor = htmlspecialchars($mac_detay[12]);
$dep_skor = htmlspecialchars($mac_detay[13]);
$sayfa_basligi = "$ev_sahibi_adi $ev_skor - $dep_skor $deplasman_adi | Canlı Takip";

$ev_class = ''; $dep_class = '';
if (is_numeric($mac_detay[12]) && is_numeric($mac_detay[13])) {
    if ($mac_detay[12] > $mac_detay[13]) { $ev_class = 'winning-team'; }
    elseif ($mac_detay[13] > $mac_detay[12]) { $dep_class = 'winning-team'; }
}

$dakika_str = $mac_detay[6];
$progress_percent = 0;
if (is_numeric($dakika_str)) { $progress_percent = (intval($dakika_str) / 90) * 100; }
elseif ($dakika_str == 'İY') { $progress_percent = 50; }
elseif (in_array($dakika_str, ['MS', 'Bitti'])) { $progress_percent = 100; }
if ($progress_percent > 100) { $progress_percent = 100; }

$sport_id = $mac_detay[36][11] ?? 1;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sayfa_basligi; ?></title>
    <style>
        :root { --bg-color: #f4f4f9; --card-bg-color: #ffffff; --text-color: #333333; --border-color: #ddd; --header-bg-color: #0d6efd; --button-bg-color: #6c757d; --button-hover-bg-color: #5a6268; --text-muted-color: #6c757d;}
        body.dark-mode { --bg-color: #121212; --card-bg-color: #1e1e1e; --text-color: #e0e0e0; --border-color: #444; --header-bg-color: #345a99; --button-bg-color: #444; --button-hover-bg-color: #555; --text-muted-color: #aaa;}
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); margin: 20px; transition: background-color 0.3s, color 0.3s; }
        .header-controls { display: flex; max-width: 600px; margin: 0 auto 20px auto; justify-content: space-between; align-items: center; gap: 10px; }
        .button { display: inline-block; padding: 10px 15px; color: white; text-decoration: none; font-weight: bold; border-radius: 5px; transition: background-color 0.2s; border: none; cursor: pointer; background-color: var(--button-bg-color); font-size: 14px; }
        .button:hover { background-color: var(--button-hover-bg-color); }
        .match-card { background-color: var(--card-bg-color); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .match-card h2 { margin-top: 0; color: var(--text-color); border-bottom: 2px solid var(--header-bg-color); padding-bottom: 10px; }
        .progress-bar-container { background-color: #e9ecef; border-radius: 5px; overflow: hidden; margin: 10px 0; }
        .progress-bar { height: 10px; width: 0; background-color: #28a745; transition: width 0.5s ease-in-out; }
        .match-details { list-style-type: none; padding: 0; line-height: 1.8; }
        .match-details li { font-size: 16px; border-bottom: 1px solid var(--border-color); padding: 10px 0; display: flex; justify-content: space-between; align-items: center; }
        .match-details li:last-child { border-bottom: none; }
        .match-details strong { color: var(--text-muted-color); }
        .goal-flash { animation: flash 1s; }
        @keyframes flash { 0% { background-color: #fff3cd; } 100% { background-color: transparent; } }
        .winning-team > span { font-weight: bold; }
    </style>
</head>
<body>
<div class="header-controls">
    <a href='canli.php?tarih=<?php echo urlencode($tarih); ?>' class='button'>&larr; Listeye Geri Dön</a>
    <button class="button" onclick="requestNotificationPermission()">🔔 Bildirim İzni</button>
    <button id="theme-toggle" class="button">🌙</button>
</div>
<div class='match-card'>
    <h2>Maç Detayları</h2>
    <div class="progress-bar-container"><div class="progress-bar" id="progress-bar" style="width: <?php echo $progress_percent; ?>%;"></div></div>
    <ul class='match-details'>
        <li class="<?php echo $ev_class; ?>"><strong id="ev-sahibi-strong">Ev Sahibi:</strong> <span id="ev-sahibi"><?php echo $ev_sahibi_adi; ?></span></li>
        <li id="skor-satiri"><strong>Skor:</strong> <span id="skor" style="font-weight: bold; font-size: 1.2em;"><?php echo "$ev_skor - $dep_skor"; ?></span></li>
        <li class="<?php echo $dep_class; ?>"><strong id="deplasman-strong">Deplasman:</strong> <span id="deplasman"><?php echo $deplasman_adi; ?></span></li>
        <hr style="width:100%; border:0; border-top: 1px solid var(--border-color); margin: 10px 0;">
        <li><strong>Durum:</strong> <span id="durum"><?php echo is_numeric($mac_detay[6]) ? htmlspecialchars($mac_detay[6]) . "'" : htmlspecialchars($mac_detay[6]); ?></span></li>
        <li><strong>Başlama Saati:</strong> <span><?php echo (isset($mac_detay[16]) ? htmlspecialchars($mac_detay[16]) : 'N/A'); ?></span></li>
        <li><strong>Lig:</strong> <span><?php echo (isset($mac_detay[36][1]) ? htmlspecialchars($mac_detay[36][1]) : 'Bilgi Yok'); ?></span></li>
        <li><strong>Tarih:</strong> <span><?php echo htmlspecialchars($tarih); ?></span></li>
    </ul>
</div>
<audio id="gol-sesi" src="gol.mp3"></audio>
<script>
    const themeToggle = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme) {
        document.body.classList.add(currentTheme);
        if (currentTheme === 'dark-mode') { themeToggle.innerText = '☀️'; }
    }
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        let theme = document.body.classList.contains('dark-mode') ? 'dark-mode' : 'light-mode';
        themeToggle.innerText = theme === 'dark-mode' ? '☀️' : '🌙';
        localStorage.setItem('theme', theme);
    });

    const evSahibiAdi = <?php echo json_encode($ev_sahibi_adi); ?>;
    const deplasmanAdi = <?php echo json_encode($deplasman_adi); ?>;
    const macKodu = '<?php echo $kod; ?>';
    const macTarihi = '<?php echo $tarih; ?>';
    const sportId = <?php echo $sport_id; ?>;

    function requestNotificationPermission() {
        if (!("Notification" in window)) { alert("Bu tarayıcı bildirimleri desteklemiyor."); }
        else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    let confirmationMessage = "Artık gol olduğunda bildirim alacaksınız.";
                    if (sportId === 2) { confirmationMessage = "Artık sayı olduğunda bildirim alacaksınız."; }
                    new Notification("Harika!", { body: confirmationMessage });
                }
            });
        }
    }

    setInterval(checkScore, 7000); 

    function checkScore() {
        fetch(`api.php?kod=${macKodu}&tarih=${macTarihi}`)
            .then(response => response.json())
            .then(yeniVeri => {
                if (yeniVeri.hata) { console.error('API Hatası:', yeniVeri.hata); return; }
                
                const mevcutSkor = document.getElementById('skor').innerText;
                const yeniSkor = `${yeniVeri[12]} - ${yeniVeri[13]}`;
                const yeniDurumRaw = yeniVeri[6];
                const yeniDurum = !isNaN(parseInt(yeniDurumRaw)) && sportId == 1 ? yeniDurumRaw + "'" : yeniDurumRaw;
                
                if (yeniSkor !== mevcutSkor) {
                    const yeniBaslik = `${evSahibiAdi} ${yeniVeri[12]} - ${yeniVeri[13]} ${deplasmanAdi} | Canlı Takip`;
                    document.title = "⚽ GOOOOOL! ⚽";
                    if (Notification.permission === "granted") {
                        let notificationTitle = "GOL OLDU!";
                        if (sportId === 2) { notificationTitle = "SAYI OLDU!"; }
                        const notificationText = `${yeniVeri[2]} ${yeniVeri[12]} - ${yeniVeri[13]} ${yeniVeri[4]}`;
                        new Notification(notificationTitle, { body: notificationText, icon: 'goal.png' });
                    }
                    setTimeout(() => { document.title = yeniBaslik; }, 4000);
                    document.getElementById('skor-satiri').classList.add('goal-flash');
                    document.getElementById('gol-sesi').play().catch(e => {});
                    setTimeout(() => { document.getElementById('skor-satiri').classList.remove('goal-flash'); }, 1000);
                }
                
                document.getElementById('skor').innerText = yeniSkor;
                document.getElementById('durum').innerText = yeniDurum;

                const evLi = document.getElementById('ev-sahibi').closest('li');
                const depLi = document.getElementById('deplasman').closest('li');
                evLi.classList.remove('winning-team');
                depLi.classList.remove('winning-team');
                if (parseInt(yeniVeri[12]) > parseInt(yeniVeri[13])) {
                    evLi.classList.add('winning-team');
                } else if (parseInt(yeniVeri[13]) > parseInt(yeniVeri[12])) {
                    depLi.classList.add('winning-team');
                }

                let dakikaStr = yeniVeri[6];
                let progressPercent = 0;
                if (!isNaN(parseInt(dakikaStr))) { progressPercent = (parseInt(dakikaStr) / 90) * 100; } 
                else if (dakikaStr == 'İY') { progressPercent = 50; } 
                else if (["MS", "Bitti"].includes(dakikaStr)) { progressPercent = 100; }
                if (progressPercent > 100) progressPercent = 100;
                document.getElementById('progress-bar').style.width = progressPercent + '%';
            })
            .catch(error => { console.error('Veri çekilirken hata oluştu:', error); });
    }
    
    document.body.addEventListener('click', () => {
        const audio = document.getElementById('gol-sesi');
        if (audio.paused) { audio.play().then(() => audio.pause()).catch(e => {}); }
    }, { once: true });
</script>
</body>
</html>