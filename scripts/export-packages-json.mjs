/**
 * Export expanded YNPackages + places to sql/seed-data.json for PHP import.
 * Run: node scripts/export-packages-json.mjs
 */
import fs from 'fs';
import path from 'path';
import vm from 'vm';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const code = fs.readFileSync(path.join(root, 'js', 'packages-data.js'), 'utf8');
const sandbox = { window: {}, console };
vm.createContext(sandbox);
vm.runInContext(code, sandbox);

const YN = sandbox.window.YNPackages;
if (!YN || !YN.all) {
  console.error('YNPackages not found');
  process.exit(1);
}

const places = {};
for (const [slug, p] of Object.entries(YN.places)) {
  places[slug] = {
    slug,
    label: p.label,
    tags: p.tags || [],
    arrive: p.arrive || '',
    sightseeing: p.sightseeing || '',
    images: p.images || [],
  };
}

const packages = YN.all.map((p, i) => ({
  slug: p.id,
  sheet: p.sheet,
  group_name: p.group,
  pickup: p.pickup,
  drop_point: p.drop,
  pickup_slug: p.pickupSlug,
  days: p.days,
  nights: p.nights,
  stay_split: p.staySplit,
  stay_summary: p.staySummary,
  destinations: p.destinations,
  dest_line: p.destLine,
  pages: p.pages,
  type: p.type,
  state: p.state,
  duration_bucket: p.duration,
  title: p.title,
  overview: p.overview,
  card_text: p.cardText,
  highlights: p.highlights,
  itinerary: p.itinerary,
  image: p.image,
  gallery: p.gallery,
  has_houseboat: p.hasHouseboat ? 1 : 0,
  accommodation: p.accommodation,
  is_published: 1,
  sort_order: i,
}));

function stubPackage(p) {
  const days = p.days;
  const nights = days - 1;
  let duration = '8-10';
  if (days <= 4) duration = '2-4';
  else if (days <= 7) duration = '5-7';
  if (p.duration) duration = p.duration;
  return {
    slug: p.slug,
    sheet: p.sheet || 'Domestic',
    group_name: p.group || p.title,
    pickup: p.pickup || '',
    drop_point: p.drop || '',
    pickup_slug: p.pickup_slug || '',
    days,
    nights,
    stay_split: '',
    stay_summary: p.stay_summary || '',
    destinations: p.destinations || [],
    dest_line: p.dest_line || '',
    pages: p.pages,
    type: p.type || 'leisure',
    state: p.state || '',
    duration_bucket: duration,
    title: p.title,
    overview: p.overview || p.card_text || '',
    card_text: p.card_text || '',
    highlights: p.highlights || [],
    itinerary: p.itinerary || [{ day: 1, title: 'Arrival', text: p.overview || p.card_text || '' }],
    image: p.image,
    gallery: p.gallery || [p.image],
    has_houseboat: 0,
    accommodation: p.accommodation || 'Comfortable hotels / resorts as per availability for your dates.',
    is_published: 1,
    sort_order: packages.length,
  };
}

const extraPackages = [
  stubPackage({ slug: 'goa-coastal', title: 'Goa Coastal Escape', pages: ['domestic'], days: 5, destinations: ['goa'], dest_line: 'North & South Goa', state: 'goa', type: 'leisure', card_text: 'Beaches, heritage walks and easy evenings by the sea.', highlights: ['Beaches', 'Fort', 'Cuisine'], image: 'goa-beach.jpg', overview: 'A relaxed coastal escape across North and South Goa with beaches, forts and local cuisine.' }),
  stubPackage({ slug: 'himachal-escape', title: 'Himachal Escape', pages: ['domestic'], days: 7, destinations: ['manali'], dest_line: 'Manali · Solang · Kasol', state: 'himachal', type: 'adventure', card_text: 'Snow peaks, pine forests and valley cafe trails.', highlights: ['Mountains', 'Adventure', 'Cafes'], image: 'hills-mist.jpg', overview: 'Hill adventure across Manali, Solang and Kasol with mountain air and cafe trails.' }),
  stubPackage({ slug: 'kashmir-valley', title: 'Kashmir Valley', pages: ['domestic'], days: 6, destinations: ['kashmir'], dest_line: 'Srinagar · Gulmarg · Pahalgam', state: 'jk', type: 'family', card_text: 'Lakes, meadows and mountain air in the valley.', highlights: ['Shikara', 'Meadows', 'Gardens'], image: 'lake.jpg', overview: 'Family-friendly valley time with shikara, meadows and gardens.' }),
  stubPackage({ slug: 'rajasthan-heritage', title: 'Rajasthan Heritage', pages: ['domestic'], days: 8, destinations: ['rajasthan'], dest_line: 'Jaipur · Jodhpur · Udaipur', state: 'rajasthan', type: 'family', duration: '8+', card_text: 'Forts, palaces and desert-tinged evenings.', highlights: ['Forts', 'Palaces', 'Markets'], image: 'rajasthan.jpg', overview: 'Heritage circuit through Jaipur, Jodhpur and Udaipur.' }),
  stubPackage({ slug: 'andaman', title: 'Andaman Islands', pages: ['domestic'], days: 6, destinations: ['goa'], dest_line: 'Port Blair · Havelock', state: 'goa', type: 'leisure', card_text: 'Turquoise waters, beaches and island pacing.', highlights: ['Beaches', 'Snorkel', 'Ferry'], image: 'island.jpg', overview: 'Island leisure between Port Blair and Havelock.' }),
  stubPackage({ slug: 'ladakh-road', title: 'Ladakh Road Trip', pages: ['domestic'], days: 8, destinations: ['manali'], dest_line: 'Leh · Nubra · Pangong', state: 'himachal', type: 'adventure', duration: '8+', card_text: 'High-altitude landscapes for prepared travellers.', highlights: ['Passes', 'Lakes', 'Monasteries'], image: 'hills-mist.jpg', overview: 'High-altitude road trip covering Leh, Nubra and Pangong.' }),
  stubPackage({ slug: 'dubai-break', title: 'Dubai City Break', pages: ['international'], days: 5, destinations: ['dubai'], dest_line: 'Dubai · Abu Dhabi', state: '', type: 'family', sheet: 'International', card_text: 'Skyline views, desert evening and curated city highlights.', highlights: ['City tour', 'Desert', 'Malls'], image: 'dubai.jpg', overview: 'Family city break with skyline views and a desert evening.' }),
  stubPackage({ slug: 'thailand-twin', title: 'Thailand Twin Cities', pages: ['international'], days: 6, destinations: ['bangkok'], dest_line: 'Bangkok · Pattaya', state: '', type: 'leisure', sheet: 'International', card_text: 'Markets, temples and coastal downtime.', highlights: ['Temples', 'Markets', 'Beach'], image: 'thailand.jpg', overview: 'Twin-city leisure across Bangkok and Pattaya.' }),
  stubPackage({ slug: 'singapore-family', title: 'Singapore Family Fun', pages: ['international'], days: 4, destinations: ['singapore'], dest_line: 'Singapore', state: '', type: 'family', sheet: 'International', card_text: 'Gardens, attractions and easy city hopping.', highlights: ['Gardens', 'Attractions', 'Food'], image: 'singapore.jpg', overview: 'Family-friendly Singapore with gardens and attractions.' }),
  stubPackage({ slug: 'maldives-escape', title: 'Maldives Quiet Escape', pages: ['international'], days: 4, destinations: ['maldives'], dest_line: 'Maldives', state: '', type: 'couple', sheet: 'International', card_text: 'Overwater calm and slow island mornings.', highlights: ['Lagoon', 'Snorkel', 'Relax'], image: 'maldives.jpg', overview: 'Quiet lagoon escape for couples.' }),
  stubPackage({ slug: 'bali-leisure', title: 'Bali Leisure Week', pages: ['international'], days: 6, destinations: ['bangkok'], dest_line: 'Ubud · Seminyak', state: '', type: 'couple', sheet: 'International', card_text: 'Rice terraces, beaches and wellness pacing.', highlights: ['Culture', 'Beach', 'Wellness'], image: 'bali.jpg', overview: 'Leisure week across Ubud and Seminyak.' }),
  stubPackage({ slug: 'vietnam-discovery', title: 'Vietnam Discovery', pages: ['international'], days: 7, destinations: ['bangkok'], dest_line: 'Hanoi · Ha Long', state: '', type: 'leisure', sheet: 'International', card_text: 'Street food, old quarters and bay views.', highlights: ['City', 'Bay cruise', 'Food'], image: 'vietnam.jpg', overview: 'Discovery journey through Hanoi and Ha Long.' }),
];

packages.push(...extraPackages);

const resorts = [
  { slug: 'misty-valley', title: 'Misty Valley Resort', location: 'Munnar', category: 'hill', summary: 'Tea-hill stay with valley views.', body: 'A calm hill resort for couples and families exploring Munnar.', image: 'resort.jpg', amenities: ['Valley view', 'Restaurant', 'Transfers on request'], gallery: ['resort.jpg', 'hills-mist.jpg'] },
  { slug: 'lagoon-house', title: 'Lagoon Edge Resort', location: 'Alleppey', category: 'backwater', summary: 'Backwater-edge rooms near the lagoons.', body: 'Wake to canal views and easy access to houseboat experiences.', image: 'kerala-backwaters.jpg', amenities: ['Lagoon view', 'Breakfast', 'Boat access'], gallery: ['kerala-backwaters.jpg', 'lake.jpg'] },
  { slug: 'canopy-lodge', title: 'Wayanad Canopy Lodge', location: 'Wayanad', category: 'forest', summary: 'Forest-edge lodge among plantations.', body: 'Ideal base for Edakkal, waterfalls and plantation walks.', image: 'forest.jpg', amenities: ['Plantation walks', 'Bonfire on request', 'Family cottages'], gallery: ['forest.jpg', 'camping.jpg'] },
  { slug: 'palm-coast', title: 'Palm Coast Resort', location: 'Kovalam', category: 'beach', summary: 'Coastal resort near lighthouse beach.', body: 'Sea breeze stays with optional Ayurveda add-ons.', image: 'beach.jpg', amenities: ['Near beach', 'Pool', 'Ayurveda on request'], gallery: ['beach.jpg', 'goa-beach.jpg'] },
  { slug: 'tea-garden', title: 'Tea Garden Retreat', location: 'Munnar', category: 'hill', summary: 'Stay among tea gardens.', body: 'Quiet rooms overlooking manicured tea slopes.', image: 'tea-plantation.jpg', amenities: ['Tea estate views', 'Guided walks', 'Fireplace lounge'], gallery: ['tea-plantation.jpg', 'hills-mist.jpg'] },
  { slug: 'sunset-bay', title: 'Sunset Bay Villas', location: 'Varkala', category: 'beach', summary: 'Cliff-side villas for sunset evenings.', body: 'Walk to the promenade and beach cafes.', image: 'resort-pool.jpg', amenities: ['Cliff views', 'Private sit-outs', 'Cafe access'], gallery: ['resort-pool.jpg', 'beach.jpg'] },
];

const getaways = [
  { slug: 'wayanad-weekend', title: 'Wayanad Weekend', location: 'Wayanad', duration: '2N/3D', summary: 'Short escape to caves, falls and plantations.', body: 'Perfect Friday–Sunday getaway from Calicut or Bangalore.', image: 'forest.jpg' },
  { slug: 'munnar-quick', title: 'Munnar Quick Escape', location: 'Munnar', duration: '2N/3D', summary: 'Tea hills in a compact itinerary.', body: 'Viewpoint mornings and cool evenings without a long tour.', image: 'tea-plantation.jpg' },
  { slug: 'alleppey-night', title: 'Alleppey Overnight', location: 'Alleppey', duration: '1N/2D', summary: 'Houseboat night on the backwaters.', body: 'Board, cruise, overnight meals and a slow morning.', image: 'kerala-backwaters.jpg' },
  { slug: 'ooty-break', title: 'Ooty Hill Break', location: 'Ooty', duration: '2N/3D', summary: 'Nilgiri air and lake walks.', body: 'Botanical garden, lake and a gentle hill weekend.', image: 'hills-mist.jpg' },
];

const gift_cards = [
  { slug: 'classic', title: 'Classic', blurb: 'A flexible gift for short getaways and experiences.', features: ['Personalised voucher', 'Valid across select packages', 'No online rates shown'], image: 'gift.jpg' },
  { slug: 'explorer', title: 'Explorer', blurb: 'For travellers who want a fuller Kerala or South circuit.', features: ['Higher value credit', 'Flexible travel window', 'Concierge assistance'], image: 'gift.jpg' },
  { slug: 'premium', title: 'Premium', blurb: 'Our most generous gift for milestone celebrations.', features: ['Priority planning', 'Premium stay upgrades on request', 'Dedicated coordinator'], image: 'gift.jpg' },
];

const investment_plans = [
  { slug: 'partner-stay', title: 'Partner Stay Programme', blurb: 'Explore property partnership opportunities with YathraNest.', features: ['Transparent briefing', 'No pressure sales', 'Suitable for long-term hosts'], image: 'resort.jpg' },
  { slug: 'experience-fund', title: 'Experience Fund', blurb: 'Learn how curated travel experiences can be supported as a partner.', features: ['Info session', 'Operational overview', 'Enquiry-only'], image: 'friends-travel.jpg' },
];

const settings = {
  phone: '+91 98765 43210',
  email: 'hello@yathranest.com',
  whatsapp: '919876543210',
  address: 'Kerala, India',
  social_instagram: '#',
  social_facebook: '#',
  social_youtube: '#',
};

const page_content = {
  home: {
    title: 'Home',
    sections: {
      hero_title: 'Travel, nested with care',
      hero_text: 'Curated Kerala and South Indian journeys, stays and experiences — pricing shared personally after you enquire.',
      cta_text: 'Request Pricing',
      intro: '',
      body: '',
      hero_image: 'kerala-backwaters.jpg',
    },
  },
  about: {
    title: 'About YathraNest',
    sections: {
      intro: 'YathraNest crafts thoughtful journeys across Kerala, South India and beyond.',
      body: '<p>We plan packages, stays, taxi services and experiences with a simple rule: browse and enquire — pricing is shared personally for your dates.</p>',
      hero_image: 'friends-travel.jpg',
    },
  },
  faq: {
    title: 'FAQ',
    sections: {
      intro: 'Common questions about planning with YathraNest.',
      body: '',
      faqs: [
        { q: 'Do you show prices online?', a: 'No. We share personalised pricing after you enquire with dates and group size.' },
        { q: 'Can you customise an itinerary?', a: 'Yes. Share preferences and we will adjust stays, pace and inclusions.' },
        { q: 'How do I book?', a: 'Submit an enquiry. Our team confirms availability and sends a quote — no online payment required on this site.' },
      ],
    },
  },
  terms: {
    title: 'Terms & Conditions',
    sections: {
      intro: 'Please read these terms before enquiring.',
      body: '<p>Enquiries are requests for information and do not constitute a confirmed booking until written confirmation is issued by YathraNest.</p>',
    },
  },
  privacy: {
    title: 'Privacy Policy',
    sections: {
      intro: 'How we handle your enquiry details.',
      body: '<p>We use the contact information you submit solely to respond to your travel enquiry and related follow-ups. We do not sell personal data.</p>',
    },
  },
};

const out = {
  admin: { email: 'admin@yathranest.com', password: 'ChangeMe123!', name: 'Admin' },
  places,
  packages,
  resorts,
  getaways,
  gift_cards,
  investment_plans,
  settings,
  page_content,
};

const outPath = path.join(root, 'sql', 'seed-data.json');
fs.writeFileSync(outPath, JSON.stringify(out, null, 2));
console.log('Wrote', outPath, 'packages:', packages.length, 'places:', Object.keys(places).length);
