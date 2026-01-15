// Province-ordered skeleton mapping (Abra -> Zamboanga Sibugay).
// Populate each province object with city/municipality => zip when available.
const PROVINCE_ZIP = {
    "Abra": {
        'BANGUED': '2800', 'BOLINEY': '2815', 'BUCAY': '2805', 'BUCLOC': '2817', 'DAGUIOMAN': '2816',
        'DANGLAS': '2825', 'DOLORES': '2801', 'LA PAZ': '2826', 'LACUB': '2821', 'LAGANGILANG': '2802',
        'LAGAYAN': '2824', 'LANGIDEN': '2807', 'LICUAN (BAAY)': '2819', 'LUBA': '2813', 'MALIBCONG': '2820',
        'MANABO': '2810', 'PEÑARRUBIA': '2804', 'PIDIGAN': '2806', 'PILAR': '2812', 'SALLAPADAN': '2818',
        'SAN ISIDRO': '2809', 'SAN JUAN': '2823', 'SAN QUINTIN': '2808', 'TAYUM': '2803', 'TINEG': '2822',
        'TUBO': '2814', 'VILLAVICIOSA': '2811'
    },
    "Agusan del Norte": {},
    "Agusan del Sur": {},
    "Aklan": {},
    "Albay": {},
    "Antique": {},
    "Apayao": {},
    "Aurora": {},
    "Basilan": {},
    "Bataan": {},
    "Batanes": {},
    "Batangas": {},
    "Benguet": {},
    "Biliran": {},
    "Bohol": {},
    "Bukidnon": {},
    "Bulacan": {},
    "Cagayan": {},
    "Camarines Norte": {},
    "Camarines Sur": {},
    "Camiguin": {},
    "Capiz": {},
    "Catanduanes": {},
    "Cavite": {},
    "Cebu": {},
    "Cotabato": {},
    "Davao de Oro": {},
    "Davao del Norte": {},
    "Davao del Sur": {},
    "Davao Occidental": {},
    "Davao Oriental": {},
    "Dinagat Islands": {},
    "Eastern Samar": {},
    "Guimaras": {},
    "Ifugao": {},
    "Ilocos Norte": {},
    "Ilocos Sur": {},
    "Iloilo": {},
    "Isabela": {},
    "Kalinga": {},
    "La Union": {},
    "Laguna": {},
    "Lanao del Norte": {},
    "Lanao del Sur": {},
    "Leyte": {},
    "Maguindanao": {},
    "Marinduque": {},
    "Masbate": {},
    "Misamis Occidental": {},
    "Misamis Oriental": {},
    "Mountain Province": {},
    "Negros Occidental": {},
    "Negros Oriental": {},
    "Northern Samar": {},
    "Nueva Ecija": {},
    "Nueva Vizcaya": {},
    "Occidental Mindoro": {},
    "Oriental Mindoro": {},
    "Palawan": {},
    "Pampanga": {},
    "Pangasinan": {},
    "Quezon": {},
    "Quirino": {},
    "Rizal": {},
    "Romblon": {},
    "Sarangani": {},
    "Siquijor": {},
    "Sorsogon": {},
    "South Cotabato": {},
    "Southern Leyte": {},
    "Sultan Kudarat": {},
    "Sulu": {},
    "Surigao del Norte": {},
    "Surigao del Sur": {},
    "Tarlac": {},
    "Tawi-Tawi": {},
    "Zambales": {},
    "Zamboanga del Norte": {},
    "Zamboanga del Sur": {},
    "Zamboanga Sibugay": {}
};


const norm = s => s ? s.toString().toUpperCase().replace(/[^A-Z0-9 ]+/g, ' ').replace(/\s+/g, ' ').trim() : '';
const PROVINCE_KEY_LOOKUP = Object.fromEntries(Object.keys(PROVINCE_ZIP).map(k => [norm(k), k]));

function resolveProvinceKey(name) {
    const key = PROVINCE_KEY_LOOKUP[norm(name)];
    return key || null;
}


async function hydrateFromJson(url = 'js/ph_locations.json') {
    if (typeof fetch !== 'function') return { added: 0, missingZip: 0, missingProv: 0, skipped: 0 };
    if (window.ZIP_JSON_HYDRATED) return window.ZIP_JSON_STATS || { added: 0, missingZip: 0, missingProv: 0, skipped: 0 };

    try {
        const resp = await fetch(url);
        if (!resp.ok) throw new Error('Failed to fetch ph_locations.json');
        const data = await resp.json();

        let added = 0, missingZip = 0, missingProv = 0, skipped = 0;

        for (const provName of Object.keys(data || {})) {
            const provKey = resolveProvinceKey(provName) || provName;
            if (!PROVINCE_ZIP[provKey]) { missingProv++; continue; }

            const entries = data[provName] || [];
            const provMap = PROVINCE_ZIP[provKey];

            for (const entry of entries) {
                const city = norm(entry && (entry.name || entry.city || entry.municipality));
                const zip = entry && entry.zip ? entry.zip.toString().trim() : '';

                if (!city) { skipped++; continue; }
                if (!zip) { missingZip++; continue; }

                provMap[city] = zip;
                added++;
            }
        }

        const stats = { added, missingZip, missingProv, skipped };
        window.ZIP_JSON_HYDRATED = true;
        window.ZIP_JSON_STATS = stats;
        return stats;
    } catch (e) {
        console.error('hydrateFromJson failed', e);
        return { added: 0, missingZip: 0, missingProv: 0, skipped: 0, error: e.message };
    }
}



function getZipCode(cityName) {
    if (!cityName) return '';
    const cleanName = cityName.toUpperCase().trim();
    // 1) Try province-grouped map (exact match)
    for (const prov of Object.keys(PROVINCE_ZIP)) {
        const map = PROVINCE_ZIP[prov];
        if (map && map[cleanName]) return map[cleanName];
    }

    // 2) Try province-grouped partial match
    for (const prov of Object.keys(PROVINCE_ZIP)) {
        const map = PROVINCE_ZIP[prov];
        if (!map) continue;
        for (const key in map) {
            if (cleanName.includes(key)) return map[key];
        }
    }



    return '';
}


async function autoGroupFlatMap(apiBase = 'https://psgc.gitlab.io/api') {
    if (window.ZIP_AUTO_GROUPED) return { grouped: 0, unmatched: 0 };


    const FLAT = {
        'BANGUED': '2800', 'BOLINEY': '2815', 'BUCAY': '2805', 'BUCLOC': '2817', 'DAGUIOMAN': '2816', 
        'DANGLAS': '2825', 'DOLORES': '2801', 'LA PAZ': '2826', 'LACUB': '2821', 'LAGANGILANG': '2802', 
        'LAGAYAN': '2824', 'LANGIDEN': '2807', 'LICUAN (BAAY)': '2819', 'LUBA': '2813', 'MALIBCONG': '2820', 
        'MANABO': '2810', 'PEÑARRUBIA': '2804', 'PIDIGAN': '2806', 'PILAR': '2812', 'SALLAPADAN': '2818', 
        'SAN ISIDRO': '2809', 'SAN JUAN': '2823', 'SAN QUINTIN': '2808', 'TAYUM': '2803', 'TINEG': '2822', 
        'TUBO': '2814', 'VILLAVICIOSA': '2811'
    
    };

    try {
        const provincesResp = await fetch(`${apiBase}/provinces/`);
        if (!provincesResp.ok) throw new Error('Failed to fetch provinces');
        const provinces = await provincesResp.json();

        // build city -> province map
        const cityToProvince = {}; // normCity => provinceName

        for (const prov of provinces) {
            const provName = prov.name || prov.province || prov; // defensive
            try {
                const citiesResp = await fetch(`${apiBase}/provinces/${prov.code}/cities-municipalities/`);
                if (!citiesResp.ok) continue;
                const cities = await citiesResp.json();
                for (const c of cities) {
                    const cn = norm(c.name || c);
                    if (cn) cityToProvince[cn] = provName;
                }
            } catch (e) {
                // ignore per-province errors
                continue;
            }
        }

        let grouped = 0, unmatched = 0;

        // Attempt exact match first using the local flat data
        for (const rawKey of Object.keys(FLAT)) {
            const z = FLAT[rawKey];
            const nk = norm(rawKey);
            if (!nk) { unmatched++; continue; }

            if (cityToProvince[nk]) {
                const provName = cityToProvince[nk];
                if (!PROVINCE_ZIP[provName]) PROVINCE_ZIP[provName] = {};
                PROVINCE_ZIP[provName][nk] = z;
                grouped++;
                continue;
            }

            // try partial match: check if any known city name is contained in nk
            let matched = false;
            for (const cityNorm in cityToProvince) {
                if (nk.includes(cityNorm) || cityNorm.includes(nk)) {
                    const provName = cityToProvince[cityNorm];
                    if (!PROVINCE_ZIP[provName]) PROVINCE_ZIP[provName] = {};
                    PROVINCE_ZIP[provName][nk] = z;
                    grouped++; matched = true; break;
                }
            }
            if (!matched) unmatched++;
        }

        window.ZIP_AUTO_GROUPED = true;
        window.ZIP_GROUP_STATS = { grouped, unmatched };
        return { grouped, unmatched };
    } catch (e) {
        console.error('autoGroupFlatMap failed', e);
        // If the auto-group fails, return conservative stats using the
        // local flat length if available.
        const unmatched = (typeof FLAT === 'object') ? Object.keys(FLAT).length : 0;
        return { grouped: 0, unmatched };
    }
}

// Run auto-grouping in background if fetch is available
if (typeof fetch === 'function') {
    hydrateFromJson().then(stats => {
        console.info('zipcodes: hydrateFromJson completed', stats);
    }).catch(() => {}).finally(() => {
        autoGroupFlatMap().then(stats => {
            console.info('zipcodes: autoGroupFlatMap completed', stats);
            try {
                // Log a compact JSON and trigger a download so you can copy/paste the grouped mapping for commit
                const json = JSON.stringify(PROVINCE_ZIP, null, 2);
                console.info('zipcodes: grouped mapping (also starting download)');
                console.log(json);
                // Trigger automatic download in the browser
                const blob = new Blob([json], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'province_zip_grouped.json';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            } catch (e) {
                console.error('zipcodes: export failed', e);
            }
        }).catch(() => {});
    });
}