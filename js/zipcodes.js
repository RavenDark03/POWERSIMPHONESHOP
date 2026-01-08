const PH_ZIP_CODES = {
    // Metro Manila
    'MANILA': '1000', 'CALOOCAN': '1400', 'LAS PINAS': '1740', 'MAKATI': '1200', 'MALABON': '1470',
    'MANDALUYONG': '1550', 'MARIKINA': '1800', 'MUNTINLUPA': '1770', 'NAVOTAS': '1485', 'PARANAQUE': '1700',
    'PASAY': '1300', 'PASIG': '1600', 'PATEROS': '1620', 'QUEZON CITY': '1100', 'SAN JUAN': '1500',
    'TAGUIG': '1630', 'VALENZUELA': '1440',

    // Bulacan
    'ANGAT': '3012', 'BALAGTAS': '3016', 'BALIUAG': '3006', 'BOCAUE': '3018', 'BULACAN': '3017',
    'BUSTOS': '3007', 'CALUMPIT': '3003', 'DONA REMEDIOS TRINIDAD': '3009', 'GUIGUINTO': '3015',
    'HAGONOY': '3002', 'MALOLOS': '3000', 'MARILAO': '3019', 'MEYCAUAYAN': '3020', 'NORZAGARAY': '3013',
    'OBANDO': '3021', 'PANDI': '3014', 'PAOMBONG': '3001', 'PLARIDEL': '3004', 'PULILAN': '3005',
    'SAN ILDEFONSO': '3010', 'SAN JOSE DEL MONTE': '3023', 'SAN MIGUEL': '3011', 'SAN RAFAEL': '3008',
    'SANTA MARIA': '3022',

    // Pampanga
    'ANGELES': '2009', 'APALIT': '2016', 'ARAYAT': '2012', 'BACOLOR': '2001', 'CANDABA': '2013',
    'FLORIDABLANCA': '2006', 'GUAGUA': '2003', 'LUBAO': '2005', 'MABALACAT': '2010', 'MACABEBE': '2018',
    'MAGALANG': '2011', 'MASANTOL': '2017', 'MEXICO': '2021', 'MINALIN': '2019', 'PORAC': '2008',
    'SAN FERNANDO': '2000', 'SAN LUIS': '2014', 'SAN SIMON': '2015', 'SANTA ANA': '2022', 'SANTA RITA': '2002',
    'SANTO TOMAS': '2020', 'SASMUAN': '2004',

    // Cebu
    'CEBU CITY': '6000', 'LAPU-LAPU': '6015', 'MANDAUE': '6014', 'TALISAY': '6045', 'DANAO': '6004',

    // Davao
    'DAVAO CITY': '8000', 'DIGOS': '8002', 'TAGUM': '8100', 'PANABO': '8105',

    // Cavite
    'BACOOR': '4102', 'CAVITE CITY': '4100', 'DASMARINAS': '4114', 'IMUS': '4103', 'TAGAYTAY': '4120',
    'TRECE MARTIRES': '4109', 'SILANG': '4118', 'GENERAL TRIAS': '4107',

    // Laguna
    'BINAN': '4024', 'CABUYAO': '4025', 'CALAMBA': '4027', 'SAN PABLO': '4000', 'SAN PEDRO': '4023',
    'SANTA ROSA': '4026', 'LOS BANOS': '4030',

    // Rizal
    'ANTIPOLO': '1870', 'CAINTA': '1900', 'TAYTAY': '1920', 'BINANGONAN': '1940', 'SAN MATEO': '1850',

    // Batangas
    'BATANGAS CITY': '4200', 'LIPA': '4217', 'TANAUAN': '4232',

    // Iloilo
    'ILOILO CITY': '5000',



};

function getZipCode(cityName) {
    if (!cityName) return '';
    const cleanName = cityName.toUpperCase().trim();

    // Direct Match
    if (PH_ZIP_CODES[cleanName]) return PH_ZIP_CODES[cleanName];

    // Partial Match (e.g. "CITY OF MALOLOS" -> "MALOLOS")
    // iterate keys
    for (let key in PH_ZIP_CODES) {
        if (cleanName.includes(key)) {
            return PH_ZIP_CODES[key];
        }
    }
    return '';
}
