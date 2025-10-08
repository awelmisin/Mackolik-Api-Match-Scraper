<?php
$tarih_param = $_GET['tarih'] ?? date('d/m/Y');
$date_parts = explode("/", $tarih_param);
if (count($date_parts) != 3 || !checkdate($date_parts[1], $date_parts[0], $date_parts[2])) {
    die('<h1>Geçersiz tarih formatı.</h1>');
}
$tarih = $tarih_param;
$timestamp = strtotime(str_replace('/', '-', $tarih));
$onceki_gun = date('d/m/Y', strtotime('-1 day', $timestamp));
$sonraki_gun = date('d/m/Y', strtotime('+1 day', $timestamp));

$veri = @file_get_contents("https://vd.mackolik.com/livedata?date=" . urlencode($tarih) . "&_=" . time());
$dizi = json_decode($veri, true);
$maclar_raw = (isset($dizi['m']) && is_array($dizi['m'])) ? $dizi['m'] : [];

$futbol_maclari = [];
$basketbol_maclari = [];
foreach ($maclar_raw as $mac) {
    if (isset($mac[36][11]) && $mac[36][11] == 2) {
        $basketbol_maclari[] = $mac;
    } else {
        $futbol_maclari[] = $mac;
    }
}

function maclariSirala($a, $b) {
    $simdiki_zaman = time();
    $zaman_asimi = 4 * 3600;
    $saat_a_ts = strtotime($a[16] ?? '00:00');
    $saat_b_ts = strtotime($b[16] ?? '00:00');
    $a_canli_durum = is_numeric($a[6]) || $a[6] == 'İY';
    $b_canli_durum = is_numeric($b[6]) || $b[6] == 'İY';
    $a_zaman_asimi = ($saat_a_ts < ($simdiki_zaman - $zaman_asimi));
    $b_zaman_asimi = ($saat_b_ts < ($simdiki_zaman - $zaman_asimi));
    $a_gercekten_canli = $a_canli_durum && !$a_zaman_asimi;
    $b_gercekten_canli = $b_canli_durum && !$b_zaman_asimi;
    if ($a_gercekten_canli && !$b_gercekten_canli) return -1;
    if (!$a_gercekten_canli && $b_gercekten_canli) return 1;
    if ($saat_a_ts == $saat_b_ts) return 0;
    return ($saat_a_ts < $saat_b_ts) ? -1 : 1;
}

usort($futbol_maclari, 'maclariSirala');
usort($basketbol_maclari, 'maclariSirala');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canlı Maç Sonuçları</title>
    <style>
        :root { --bg-color: #f4f4f9; --card-bg-color: #ffffff; --text-color: #333333; --border-color: #ddd; --header-bg-color: #343a40; --header-text-color: #ffffff; --row-hover-color: #e9ecef; --row-even-color: #f8f9fa; --button-bg-color: #6c757d; --link-color: #0d6efd; --star-favorited-color: #ffc107; --star-default-color: #ccc;}
        body.dark-mode { --bg-color: #121212; --card-bg-color: #1e1e1e; --text-color: #e0e0e0; --border-color: #444; --header-bg-color: #000000; --header-text-color: #e0e0e0; --row-hover-color: #333; --row-even-color: #2c2c2c; --button-bg-color: #444; --link-color: #4dabf7; --star-default-color: #555;}
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); margin: 0; padding: 20px; transition: background-color 0.3s, color 0.3s; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1, h2 { border-bottom: 2px solid var(--link-color); padding-bottom: 10px; }
        h3 { margin-top: 30px; font-size: 1.1em; background-color: var(--row-even-color); padding: 10px; border-radius: 5px 5px 0 0; border-bottom: none;}
        table { width: 100%; border-collapse: collapse; margin-top: 0; background-color: var(--card-bg-color); box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: 1px solid var(--border-color); border-top: none; }
        th, td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); text-align: left; white-space: nowrap; }
        th { background-color: var(--header-bg-color); color: var(--header-text-color); font-size: 14px; }
        td.center-align { text-align: center; }
        tr:nth-child(even):not(:hover) { background-color: var(--row-even-color); }
        tr:hover { background-color: var(--row-hover-color); }
        a { color: var(--link-color); text-decoration: none; font-weight: bold; }
        .header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .date-navigation { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .nav-button { padding: 10px 15px; background-color: var(--card-bg-color); border: 1px solid var(--border-color); border-radius: 5px; text-decoration: none; font-weight: normal; }
        .sport-tabs { display: flex; border-bottom: 2px solid var(--link-color); margin-bottom: 20px; }
        .tab-button { padding: 10px 20px; cursor: pointer; background-color: transparent; border: none; font-size: 1.2em; color: var(--text-muted-color); font-weight: bold; border-bottom: 3px solid transparent; margin-bottom: -2px; }
        .tab-button.active { color: var(--link-color); border-bottom-color: var(--link-color); }
        .sport-container { display: none; }
        .winning-team { font-weight: bold; }
        .favorite-star { cursor: pointer; font-size: 20px; color: var(--star-default-color); user-select: none; text-align: center; }
        .favorite-star.favorited { color: var(--star-favorited-color); }
        .flash { animation: flash-row 1.2s; }
        @keyframes flash-row { 50% { background-color: #ffc107; } }
        #debug-info { position: fixed; bottom: 10px; right: 10px; background-color: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 5px; font-size: 12px; z-index: 1000; }
        #debug-info.error { background-color: #d9534f; }
		.signature {
    text-align: center;
    margin-bottom: 20px; /* Altındaki sekmelerle arasına boşluk koyar */
    color: var(--text-muted-color);
    font-size: 14px;
}
    </style>
</head>
<body>
<div class="container">
    <div class="header-controls">
        <div class="date-navigation">
            <a href="?tarih=<?php echo $onceki_gun; ?>" class="nav-button">&larr; Önceki Gün</a>
            <h1 style="border:none; margin:0; font-size: 1.5em;"><?php echo htmlspecialchars($tarih); ?></h1>
            <a href="?tarih=<?php echo $sonraki_gun; ?>" class="nav-button">Sonraki Gün &rarr;</a>
        </div>
        <button id="theme-toggle" style="margin-left:20px; padding:10px; cursor:pointer; border-radius:5px; border:1px solid var(--border-color); background-color:var(--card-bg-color); color:var(--text-color);">🌙</button>
    </div>
	<div class="signature">
	<p>Bu uygulama, <strong>www.github.com/awelmisin</strong> tarafından geliştirilmiştir.</p> Bağışlar için: <strong>https://buymeacoffee.com/awelmisin</strong>
	<p>USDT: <strong>0x56c0c52c284031e12c3b085871d7fceadd933ec9</strong>
    </div>
    <div class="sport-tabs">
        <button class="tab-button" onclick="showSport('futbol')">⚽ Futbol</button>
        <button class="tab-button" onclick="showSport('basketbol')">🏀 Basketbol</button>
        <button class="tab-button" onclick="showSport('favorites')">⭐ Takip Ettiklerim</button>
    </div>

    <div id="favorites-container" class="sport-container"></div>

    <div id="futbol-container" class="sport-container">
        <h2>Canlı & Oynanacak Maçlar</h2>
        <?php if (count($futbol_maclari) > 0): ?>
            <table>
                <tr><th></th><th>İ.Kodu</th><th>Saat</th><th>Lig</th><th>Dk</th><th>Ev Sahibi</th><th>Skor</th><th>Deplasman</th></tr>
                <?php foreach ($futbol_maclari as $mac): 
                    $unique_id = htmlspecialchars($mac[0]); $mac_kodu = htmlspecialchars($mac[14]);
                    $ev_class = ''; $dep_class = '';
                    if (is_numeric($mac[12]) && is_numeric($mac[13])) {
                        if ($mac[12] > $mac[13]) { $ev_class = 'winning-team'; }
                        elseif ($mac[13] > $mac[12]) { $dep_class = 'winning-team'; }
                    }
                ?>
                    <tr id="mac-<?php echo $unique_id; ?>">
                        <td class="center-align"><span class="favorite-star" data-id="<?php echo $unique_id; ?>">&#9734;</span></td>
                        <td class="center-align"><a href="maclar.php?kod=<?php echo $mac_kodu; ?>&tarih=<?php echo urlencode($tarih); ?>&focus=<?php echo ($mac[36][0] ?? ''); ?>"><?php echo $mac_kodu; ?></a></td>
                        <td class="center-align"><?php echo htmlspecialchars($mac[16] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($mac[36][1] ?? 'N/A'); ?></td>
                        <td class="center-align" id="dakika-<?php echo $unique_id; ?>"><?php echo is_numeric($mac[6]) ? htmlspecialchars($mac[6]) . "'" : htmlspecialchars($mac[6]); ?></td>
                        <td class="<?php echo $ev_class; ?>"><?php echo htmlspecialchars($mac[2]); ?></td>
                        <td class="center-align" id="skor-<?php echo $unique_id; ?>" style="font-weight:bold;"><?php echo htmlspecialchars($mac[12]) . " - " . htmlspecialchars($mac[13]); ?></td>
                        <td class="<?php echo $dep_class; ?>"><?php echo htmlspecialchars($mac[4]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?><p>Bu tarihte gösterilecek futbol maçı bulunmamaktadır.</p><?php endif; ?>
    </div>
    
    <div id="basketbol-container" class="sport-container">
         <?php if (count($basketbol_maclari) > 0): ?>
            <table>
                <tr><th></th><th>İ.Kodu</th><th>Saat</th><th>Lig</th><th>Per.</th><th>Ev Sahibi</th><th>Skor</th><th>Deplasman</th></tr>
                <?php foreach ($basketbol_maclari as $mac): 
                    $unique_id = htmlspecialchars($mac[0]); $mac_kodu = htmlspecialchars($mac[14]);
                     $ev_class = ''; $dep_class = '';
                    if (is_numeric($mac[12]) && is_numeric($mac[13])) {
                        if ($mac[12] > $mac[13]) { $ev_class = 'winning-team'; }
                        elseif ($mac[13] > $mac[12]) { $dep_class = 'winning-team'; }
                    }
                ?>
                    <tr id="mac-<?php echo $unique_id; ?>">
                        <td class="center-align"><span class="favorite-star" data-id="<?php echo $unique_id; ?>">&#9734;</span></td>
                        <td class="center-align"><a href="maclar.php?kod=<?php echo $mac_kodu; ?>&tarih=<?php echo urlencode($tarih); ?>&focus=<?php echo ($mac[36][0] ?? ''); ?>"><?php echo $mac_kodu; ?></a></td>
                        <td class="center-align"><?php echo htmlspecialchars($mac[16] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($mac[36][1] ?? 'N/A'); ?></td>
                        <td class="center-align" id="dakika-<?php echo $unique_id; ?>"><?php echo htmlspecialchars($mac[6]); ?></td>
                        <td class="<?php echo $ev_class; ?>"><?php echo htmlspecialchars($mac[2]); ?></td>
                        <td class="center-align" id="skor-<?php echo $unique_id; ?>" style="font-weight:bold;"><?php echo htmlspecialchars($mac[12]) . " - " . htmlspecialchars($mac[13]); ?></td>
                        <td class="<?php echo $dep_class; ?>"><?php echo htmlspecialchars($mac[4]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?><p>Bu tarihte gösterilecek basketbol maçı bulunmamaktadır.</p><?php endif; ?>
    </div>

    <div id="debug-info">Veri bekleniyor...</div>
    <audio id="gol-sesi-main" src="gol.mp3"></audio>
</div>

<script>
    let allMatchesData = <?php echo json_encode($maclar_raw); ?>;
    const guncelTarih = '<?php echo $tarih; ?>';
    let minuteCounters = {};
    const originalTitle = document.title;
    const debugInfo = document.getElementById('debug-info');

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

    function showSport(sportName) {
        document.querySelectorAll('.sport-container').forEach(c => c.style.display = 'none');
        document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
        document.getElementById(sportName + '-container').style.display = 'block';
        document.querySelector(`.tab-button[onclick="showSport('${sportName}')"]`).classList.add('active');
        localStorage.setItem('selectedSport', sportName);
    }

    let favorites = JSON.parse(localStorage.getItem('favoriteMatches')) || [];
    function toggleFavorite(event) {
        const star = event.target;
        const matchId = star.dataset.id;
        const index = favorites.indexOf(matchId);
        if (index > -1) { favorites.splice(index, 1); } else { favorites.push(matchId); }
        localStorage.setItem('favoriteMatches', JSON.stringify(favorites));
        updateAllStarAppearances();
        renderFavorites();
    }
    function updateAllStarAppearances() {
        document.querySelectorAll('.favorite-star').forEach(star => {
            if (favorites.includes(star.dataset.id)) {
                star.classList.add('favorited');
                star.innerHTML = '&#9733;';
            } else {
                star.classList.remove('favorited');
                star.innerHTML = '&#9734;';
            }
        });
    }
    function renderFavorites() {
        const container = document.getElementById('favorites-container');
        if (favorites.length === 0) {
            container.innerHTML = ''; return;
        }
        let favoriteMatchObjects = allMatchesData.filter(mac => favorites.includes(mac[0].toString()));
        favoriteMatchObjects.sort(jsMaclariSirala);

        let html = `<h2>⭐ Takip Ettiklerim</h2><table>`;
        html += `<tr><th></th><th>İ.Kodu</th><th>Saat</th><th>Lig</th><th>Dk/Per.</th><th>Ev Sahibi</th><th>Skor</th><th>Deplasman</th></tr>`;
        favoriteMatchObjects.forEach(mac => {
            const uniqueId = mac[0];
            const macKodu = mac[14];
            const saat = mac[16] || '-';
            const lig = mac[36]?.[1] || 'N/A';
            const isFootball = mac[36]?.[11] != 2;
            const durum = !isNaN(parseInt(mac[6])) && isFootball ? mac[6] + "'" : mac[6];
            let ev_class = ''; let dep_class = '';
            if (!isNaN(parseInt(mac[12])) && !isNaN(parseInt(mac[13]))) {
                if (parseInt(mac[12]) > parseInt(mac[13])) { ev_class = 'winning-team'; }
                else if (parseInt(mac[13]) > parseInt(mac[12])) { dep_class = 'winning-team'; }
            }
            html += `<tr id="fav-mac-${uniqueId}">
                        <td class="center-align"><span class="favorite-star favorited" data-id="${uniqueId}">&#9733;</span></td>
                        <td class="center-align"><a href="maclar.php?kod=${macKodu}&tarih=${guncelTarih}">${macKodu}</a></td>
                        <td class="center-align">${saat}</td>
                        <td>${lig}</td>
                        <td class="center-align" id="dakika-fav-${uniqueId}">${durum}</td>
                        <td class="${ev_class}">${mac[2]}</td>
                        <td class="center-align" id="skor-fav-${uniqueId}" style="font-weight:bold;">${mac[12]} - ${mac[13]}</td>
                        <td class="${dep_class}">${mac[4]}</td>
                    </tr>`;
        });
        html += '</table>';
        container.innerHTML = html;
        container.querySelectorAll('.favorite-star').forEach(star => star.addEventListener('click', toggleFavorite));
    }

    function jsMaclariSirala(a, b) {
        const simdikiZaman = Date.now() / 1000;
        const zamanAsimi = 4 * 3600;
        const saat_a_ts = new Date(`1970-01-01T${a[16] || '00:00'}:00Z`).getTime() / 1000;
        const saat_b_ts = new Date(`1970-01-01T${b[16] || '00:00'}:00Z`).getTime() / 1000;
        const a_canli_durum = !isNaN(parseInt(a[6])) || a[6] == 'İY';
        const b_canli_durum = !isNaN(parseInt(b[6])) || b[6] == 'İY';
        const a_zaman_asimi = (saat_a_ts < (simdikiZaman - zamanAsimi));
        const b_zaman_asimi = (saat_b_ts < (simdikiZaman - zamanAsimi));
        const a_gercekten_canli = a_canli_durum && !a_zaman_asimi;
        const b_gercekten_canli = b_canli_durum && !b_zaman_asimi;
        if (a_gercekten_canli && !b_gercekten_canli) return -1;
        if (!a_gercekten_canli && b_gercekten_canli) return 1;
        return saat_a_ts - saat_b_ts;
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        updateAllStarAppearances();
        renderFavorites();
        document.querySelectorAll('.favorite-star').forEach(star => star.addEventListener('click', toggleFavorite));
        const lastSelectedSport = localStorage.getItem('selectedSport') || 'futbol';
        showSport(lastSelectedSport);
        updateScoresAndCounters();
    });

    function updateScoresAndCounters() {
        debugInfo.innerText = 'Veri çekiliyor...';
        debugInfo.classList.remove('error');

        fetch(`https://vd.mackolik.com/livedata?date=${guncelTarih}&_=${new Date().getTime()}`)
            .then(response => {
                if (!response.ok) { throw new Error('API yanıt vermiyor (HTTP ' + response.status + ')'); }
                return response.json();
            })
            .then(data => {
                if (!Array.isArray(data.m)) { debugInfo.innerText = 'Gelen veri hatalı veya boş.'; return; }
                
                window.allMatchesData = data.m;
                
                allMatchesData.forEach(mac => {
                    const uniqueId = mac[0].toString();
                    const macRow = document.getElementById(`mac-${uniqueId}`);
                    
                    const isFootball = mac[36]?.[11] != 2;
                    const yeniDakika = !isNaN(parseInt(mac[6])) && isFootball ? mac[6] + "'" : mac[6];
                    const yeniSkor = `${mac[12]} - ${mac[13]}`;
                    
                    const dakikaEl = document.getElementById(`dakika-${uniqueId}`);
                    const skorEl = document.getElementById(`skor-${uniqueId}`);
                    
                    if (skorEl && skorEl.innerText !== yeniSkor) {
                        skorEl.innerText = yeniSkor;
                        if (macRow) { macRow.classList.add('flash'); setTimeout(() => macRow.classList.remove('flash'), 1200); }

                        if (favorites.includes(uniqueId) && isFootball) { 
                            document.title = "⚽ GOOOOOL! ⚽";
                            document.getElementById('gol-sesi-main')?.play().catch(e => {});
                            setTimeout(() => { document.title = originalTitle; }, 5000);
                        }
                    }
                    
                    if (dakikaEl) {
                        if (minuteCounters[uniqueId]) { clearInterval(minuteCounters[uniqueId]); delete minuteCounters[uniqueId]; }
                        dakikaEl.innerText = yeniDakika;
                        if (!isNaN(parseInt(mac[6])) && isFootball) {
                            let currentMinute = parseInt(mac[6]);
                            minuteCounters[uniqueId] = setInterval(() => {
                                currentMinute++;
                                const currentDakikaEl = document.getElementById(`dakika-${uniqueId}`);
                                if(currentDakikaEl) currentDakikaEl.innerText = currentMinute + "'";
                            }, 60000);
                        }
                    }
                    
                    if (macRow) {
                        const evSahibiEl = macRow.cells[5];
                        const deplasmanEl = macRow.cells[7];
                        if (evSahibiEl && deplasmanEl) {
                            evSahibiEl.classList.remove('winning-team');
                            deplasmanEl.classList.remove('winning-team');
                            if (parseInt(mac[12]) > parseInt(mac[13])) { evSahibiEl.classList.add('winning-team'); } 
                            else if (parseInt(mac[13]) > parseInt(mac[12])) { deplasmanEl.classList.add('winning-team'); }
                        }
                    }
                });
                renderFavorites();
                debugInfo.innerText = 'Son Güncelleme: ' + new Date().toLocaleTimeString('tr-TR');
            })
            .catch(error => {
                console.error("Skorlar güncellenirken hata oluştu:", error);
                debugInfo.innerText = 'Hata: ' + error.message;
                debugInfo.classList.add('error');
            });
    }
    
    setInterval(updateScoresAndCounters, 20000);
</script>

</body>
</html>