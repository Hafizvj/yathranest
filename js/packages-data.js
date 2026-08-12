/**
 * YathraNest — Package catalog from OUR PACKAGES.xlsx
 * Stay splits expand into titles, overviews, highlights and day-by-day itineraries.
 */
(function () {
  var PLACES = {
    wayanad: {
      label: "Wayanad",
      images: ["forest.jpg", "camping.jpg", "hills-mist.jpg", "waterfall.jpg", "mountains.jpg"],
      tags: ["Edakkal Caves", "Waterfalls", "Plantations"],
      arrive: "Drive into Wayanad through ghats and spice country. Check in and spend the evening at leisure among the plantations.",
      sightseeing: "Visit Edakkal Caves, Soochipara or Meenmutty Falls, and a viewpoint such as Banasura. Optional Chembra or trekking on request. Evening at leisure.",
    },
    kozhikode: {
      label: "Kozhikode",
      images: ["beach.jpg", "goa-beach.jpg", "city.jpg", "village.jpg"],
      tags: ["Kappad Beach", "City markets", "Coast"],
      arrive: "Continue to Kozhikode. Check in and stroll the beach or SM Street in the evening.",
      sightseeing: "Explore Kappad Beach, city markets and a relaxed coastal evening. Optional Beypore or temple stop on request.",
    },
    mysore: {
      label: "Mysore",
      images: ["temple.jpg", "rajasthan.jpg", "city.jpg", "resort.jpg"],
      tags: ["Mysore Palace", "Gardens", "Markets"],
      arrive: "Drive to Mysore. Check in and visit the illuminated palace precincts or Devaraja Market if time allows.",
      sightseeing: "Visit Mysore Palace, Chamundi Hills and Brindavan Gardens. Evening at leisure in the royal city.",
    },
    ooty: {
      label: "Ooty",
      images: ["waterfall.jpg", "hills-mist.jpg", "tea-plantation.jpg", "lake.jpg", "mountains.jpg"],
      tags: ["Nilgiri hills", "Botanical garden", "Tea estates"],
      arrive: "Climb into the Nilgiris. Check in at Ooty and enjoy a cool evening at leisure.",
      sightseeing: "Visit the Botanical Garden, Ooty Lake, Doddabetta or a tea estate. Optional toy-train stretch subject to availability.",
    },
    coorg: {
      label: "Coorg",
      images: ["forest.jpg", "tea-plantation.jpg", "waterfall.jpg", "village.jpg", "resort.jpg"],
      tags: ["Coffee estates", "Abbey Falls", "Madikeri"],
      arrive: "Drive into Coorg’s coffee hills. Check in around Madikeri and ease into the evening.",
      sightseeing: "Visit Abbey Falls, Raja’s Seat, a coffee plantation and Madikeri Fort. Local Kodava meals on request.",
    },
    chikmagalur: {
      label: "Chikmagalur",
      images: ["hills-mist.jpg", "mountains.jpg", "tea-plantation.jpg", "camping.jpg"],
      tags: ["Mullayanagiri", "Coffee hills", "Viewpoints"],
      arrive: "Continue to Chikmagalur. Check in and enjoy a quiet hill evening.",
      sightseeing: "Visit Mullayanagiri or Baba Budangiri viewpoints and a coffee estate. Pace stays gentle in the hills.",
    },
    munnar: {
      label: "Munnar",
      images: ["tea-plantation.jpg", "hills-mist.jpg", "mountains.jpg", "waterfall.jpg", "lake.jpg"],
      tags: ["Tea gardens", "Viewpoints", "Eravikulam"],
      arrive: "Scenic drive to Munnar with photo stops. Check in and spend the evening in the tea hills.",
      sightseeing: "Visit tea plantations, a viewpoint such as Top Station or Echo Point, and the tea museum. Eravikulam subject to park rules.",
    },
    thekkady: {
      label: "Thekkady",
      images: ["forest.jpg", "lake.jpg", "camping.jpg", "village.jpg"],
      tags: ["Periyar", "Spice gardens", "Wildlife"],
      arrive: "Drive to Thekkady. Spice garden visit and evening at leisure by the sanctuary.",
      sightseeing: "Spice plantation walk and optional Periyar boat safari or cultural show, subject to availability.",
    },
    gavi: {
      label: "Gavi",
      images: ["camping.jpg", "forest.jpg", "lake.jpg", "mountains.jpg"],
      tags: ["Gavi forest", "Wildlife", "Bamboo raft"],
      arrive: "Head into the Gavi forest belt. Check in at a simple forest stay if included, or return to Thekkady by evening.",
      sightseeing: "Guided forest trail and optional bamboo rafting / wildlife watching in the Gavi–Vallakadavu belt, subject to permits.",
    },
    alleppey: {
      label: "Alleppey",
      images: ["kerala-backwaters.jpg", "lake.jpg", "village.jpg", "resort-pool.jpg"],
      tags: ["Houseboat", "Backwaters", "Village life"],
      arrive: "Board a houseboat or check in by the backwaters. Cruise the canals, enjoy onboard meals and a slow sunset.",
      sightseeing: "Village walk, canal cruise or a second stretch on the water. Unhurried backwater morning before onward travel.",
    },
    athirappilly: {
      label: "Athirappilly",
      images: ["waterfall.jpg", "forest.jpg", "camping.jpg", "mountains.jpg"],
      tags: ["Athirappilly Falls", "Vazhachal", "River forest"],
      arrive: "Drive to Athirappilly. Check in near the falls and visit the viewpoint in the evening if light allows.",
      sightseeing: "Athirappilly and Vazhachal Falls with forest-edge walks. Optional river-bank time before checkout.",
    },
    kochi: {
      label: "Kochi",
      images: ["city.jpg", "beach.jpg", "friends-travel.jpg", "resort.jpg"],
      tags: ["Fort Kochi", "Chinese nets", "Harbour city"],
      arrive: "Arrive in Kochi. Check in and explore Fort Kochi — Chinese fishing nets, harbour walk and cafe streets — at an easy pace.",
      sightseeing: "Fort Kochi heritage walk, Mattancherry / Jew Town and a relaxed harbour evening. Optional Kathakali on request.",
    },
    vagamon: {
      label: "Vagamon",
      images: ["hills-mist.jpg", "mountains.jpg", "camping.jpg", "tea-plantation.jpg"],
      tags: ["Meadows", "Pine forest", "Viewpoints"],
      arrive: "Climb to Vagamon. Check in and enjoy meadow and pine-forest views in the evening.",
      sightseeing: "Vagamon meadows, pine forest and a viewpoint such as Thangal para. Optional paragliding in season.",
    },
    varkala: {
      label: "Varkala",
      images: ["beach.jpg", "goa-beach.jpg", "couple-travel.jpg", "resort-pool.jpg"],
      tags: ["Cliff beach", "Arabian Sea", "Sunset"],
      arrive: "Drive to Varkala. Check in near the cliff and walk the promenade for sunset.",
      sightseeing: "Papanasam Beach, cliff cafes and a leisurely sea morning. Optional spring or temple visit.",
    },
    kovalam: {
      label: "Kovalam",
      images: ["goa-beach.jpg", "beach.jpg", "resort-pool.jpg", "maldives.jpg"],
      tags: ["Lighthouse beach", "Coast", "Ayurveda"],
      arrive: "Continue to Kovalam. Check in and spend the evening on the crescent beaches.",
      sightseeing: "Lighthouse Beach and a slow coastal day. Optional Ayurveda session on request.",
    },
    trivandrum: {
      label: "Trivandrum",
      images: ["temple.jpg", "city.jpg", "beach.jpg", "rajasthan.jpg"],
      tags: ["Padmanabhaswamy", "Kovalam coast", "City"],
      arrive: "Drive to Trivandrum. Check in and visit the temple precincts or Shanghumugham if time allows.",
      sightseeing: "Padmanabhaswamy Temple (dress code applies), Napier Museum area or a short coastal stop before onward travel.",
    },
    valparai: {
      label: "Valparai",
      images: ["tea-plantation.jpg", "forest.jpg", "hills-mist.jpg", "mountains.jpg"],
      tags: ["Tea estates", "Wildlife", "Hairpin ghats"],
      arrive: "Climb the Valparai ghats through tea estates. Check in and watch for lion-tailed macaques along the estate roads.",
      sightseeing: "Estate viewpoints, Sholayar or Nallamudi and a gentle wildlife drive. Cool evenings in the Anamalais.",
    },
    kodaikanal: {
      label: "Kodaikanal",
      images: ["lake.jpg", "hills-mist.jpg", "mountains.jpg", "waterfall.jpg"],
      tags: ["Kodai Lake", "Coaker’s Walk", "Pine forest"],
      arrive: "Drive up to Kodaikanal. Check in and walk Coaker’s Walk or the lake in the evening.",
      sightseeing: "Kodai Lake, Coaker’s Walk, Pillar Rocks or a pine-forest trail. Optional boat ride on the lake.",
    },
    lakshadweep: {
      label: "Lakshadweep",
      images: ["island.jpg", "maldives.jpg", "beach.jpg", "resort-pool.jpg"],
      tags: ["Lagoon", "Snorkel", "Island stay"],
      arrive: "Fly to Agatti (permits required). Transfer to your island stay and settle into lagoon time.",
      sightseeing: "Lagoon snorkelling, beach walks and a slow island day. Water sports as permitted for your island.",
    },
  };

  var IMAGE_POOL = [
    "forest.jpg",
    "tea-plantation.jpg",
    "kerala-backwaters.jpg",
    "hills-mist.jpg",
    "beach.jpg",
    "city.jpg",
    "waterfall.jpg",
    "temple.jpg",
    "island.jpg",
    "lake.jpg",
    "mountains.jpg",
    "camping.jpg",
    "village.jpg",
    "goa-beach.jpg",
    "resort.jpg",
    "resort-pool.jpg",
    "couple-travel.jpg",
    "friends-travel.jpg",
    "maldives.jpg",
    "rajasthan.jpg",
    "suite.jpg",
    "resort-room.jpg",
  ];

  var imageUsage = {};
  IMAGE_POOL.forEach(function (img) {
    imageUsage[img] = 0;
  });

  function hashId(id) {
    var h = 0;
    for (var i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) >>> 0;
    return h;
  }

  function candidatesFor(dests, type) {
    var seen = {};
    var list = [];
    function add(img) {
      if (!img || seen[img]) return;
      seen[img] = true;
      list.push(img);
    }
    dests.forEach(function (d) {
      var place = PLACES[d];
      if (place && place.images) place.images.forEach(add);
    });
    if (type === "couple") ["couple-travel.jpg", "resort-pool.jpg", "suite.jpg"].forEach(add);
    if (type === "adventure") ["camping.jpg", "mountains.jpg", "forest.jpg", "waterfall.jpg"].forEach(add);
    if (type === "family") ["friends-travel.jpg", "resort.jpg", "village.jpg"].forEach(add);
    if (type === "heritage") ["temple.jpg", "rajasthan.jpg", "city.jpg"].forEach(add);
    if (type === "leisure") ["lake.jpg", "beach.jpg", "resort-pool.jpg"].forEach(add);
    IMAGE_POOL.forEach(add);
    return list;
  }

  function pickImage(dests, type, id) {
    var primary = [];
    var seen = {};
    function addPrimary(img) {
      if (!img || seen[img]) return;
      seen[img] = true;
      primary.push(img);
    }
    dests.forEach(function (d) {
      var place = PLACES[d];
      if (place && place.images) place.images.forEach(addPrimary);
    });
    if (type === "couple") ["couple-travel.jpg", "suite.jpg"].forEach(addPrimary);
    if (type === "adventure") ["camping.jpg", "mountains.jpg"].forEach(addPrimary);
    if (type === "heritage") ["temple.jpg", "rajasthan.jpg"].forEach(addPrimary);

    var pool = primary.length ? primary : IMAGE_POOL.slice();
    var minUsed = Infinity;
    pool.forEach(function (img) {
      minUsed = Math.min(minUsed, imageUsage[img] || 0);
    });
    // If destination options are already saturated, open the full pool for balance
    if (minUsed >= 3) {
      IMAGE_POOL.forEach(function (img) {
        if (!seen[img]) {
          seen[img] = true;
          pool.push(img);
        }
      });
    }

    var best = pool[0];
    var bestScore = Infinity;
    var salt = hashId(id);
    var last = pickImage.last || "";
    pool.forEach(function (img, idx) {
      var used = imageUsage[img] || 0;
      var score = used * 100 + ((salt + idx * 13) % 40);
      if (img === last) score += 500;
      if (score < bestScore) {
        bestScore = score;
        best = img;
      }
    });
    imageUsage[best] = (imageUsage[best] || 0) + 1;
    pickImage.last = best;
    return best;
  }

  function pickGallery(dests, type, hero, id) {
    var affinity = {};
    dests.forEach(function (d) {
      var place = PLACES[d];
      if (!place || !place.images) return;
      place.images.forEach(function (img, i) {
        affinity[img] = Math.max(affinity[img] || 0, 40 - i * 5);
      });
    });
    var candidates = candidatesFor(dests, type).filter(function (img) {
      return img !== hero;
    });
    var gallery = [hero];
    var salt = hashId(id + "-gallery");
    candidates
      .slice()
      .sort(function (a, b) {
        var fa = affinity[a] || 0;
        var fb = affinity[b] || 0;
        if (fb !== fa) return fb - fa;
        var ua = imageUsage[a] || 0;
        var ub = imageUsage[b] || 0;
        if (ua !== ub) return ua - ub;
        return ((salt + a.charCodeAt(0)) % 50) - ((salt + b.charCodeAt(0)) % 50);
      })
      .forEach(function (img) {
        if (gallery.length < 4) gallery.push(img);
      });
    return gallery;
  }

  Object.keys(PLACES).forEach(function (key) {
    var place = PLACES[key];
    if (place.images && place.images.length) place.image = place.images[0];
  });

  var PLACE_ALIASES = [
    ["chikmanglore", "chikmagalur"],
    ["chikmanglur", "chikmagalur"],
    ["athirappilli", "athirappilly"],
    ["athirapalli", "athirappilly"],
    ["thekkady gavi", "thekkady gavi"],
    ["tvm", "trivandrum"],
  ];

  var PLACE_KEYS = Object.keys(PLACES).sort(function (a, b) {
    return b.length - a.length;
  });

  var RAW = [
    // CALICUT
    { id: "clt-wayanad-2d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Calicut", days: 2, stay: "wayanad", pages: ["kerala"], type: "leisure" },
    { id: "clt-wayanad-3d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Calicut", days: 3, stay: "wayanad", pages: ["kerala"], type: "leisure" },
    { id: "clt-wayanad-4d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Calicut", days: 4, stay: "wayanad", pages: ["kerala"], type: "adventure" },
    { id: "clt-wayanad-5d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Calicut", days: 5, stay: "wayanad", pages: ["kerala"], type: "adventure" },
    { id: "clt-wayanad-kozhikode-4d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Calicut", days: 4, stay: "wayanad kozhikode", pages: ["kerala"], type: "leisure" },
    { id: "clt-wayanad-kozhikode-5d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Calicut", days: 5, stay: "wayanad kozhikode", pages: ["kerala"], type: "leisure" },
    { id: "clt-wayanad-mysore-3d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Mysore", days: 3, stay: "2 wayanad 0 mysore", pages: ["kerala", "south"], type: "leisure" },
    { id: "clt-wayanad-mysore-4d", sheet: "CALICUT", group: "Wayanad", pickup: "Calicut", drop: "Mysore", days: 4, stay: "2 wayanad 1 mysore", pages: ["kerala", "south"], type: "heritage" },
    { id: "clt-wayanad-ooty-4d", sheet: "CALICUT", group: "Wayanad Ooty", pickup: "Calicut", drop: "Coimbatore", days: 4, stay: "wayanad ooty", pages: ["kerala", "south"], type: "family" },
    { id: "clt-wayanad-ooty-5d", sheet: "CALICUT", group: "Wayanad Ooty", pickup: "Calicut", drop: "Coimbatore", days: 5, stay: "wayanad ooty", pages: ["kerala", "south"], type: "family" },
    { id: "clt-wayanad-ooty-6d", sheet: "CALICUT", group: "Wayanad Ooty", pickup: "Calicut", drop: "Coimbatore", days: 6, stay: "wayanad ooty", pages: ["kerala", "south"], type: "family" },
    { id: "clt-wayanad-coorg-4d", sheet: "CALICUT", group: "Wayanad Coorg", pickup: "Calicut", drop: "Mysore", days: 4, stay: "2 wayanad 1 coorg", pages: ["kerala", "south"], type: "couple" },
    { id: "clt-wayanad-coorg-5d", sheet: "CALICUT", group: "Wayanad Coorg", pickup: "Calicut", drop: "Mysore", days: 5, stay: "2 wayanad 2 coorg", pages: ["kerala", "south"], type: "couple" },
    { id: "clt-wayanad-coorg-mysore-5d", sheet: "CALICUT", group: "Wayanad Coorg", pickup: "Calicut", drop: "Mysore", days: 5, stay: "2 wayanad 2 coorg 0 mysore", pages: ["kerala", "south"], type: "family" },
    { id: "clt-wayanad-coorg-mysore-6d", sheet: "CALICUT", group: "Wayanad Coorg", pickup: "Calicut", drop: "Mysore", days: 6, stay: "2 wayanad 2 coorg 1 mysore", pages: ["kerala", "south"], type: "family" },
    { id: "clt-wayanad-ooty-mysore-5d", sheet: "CALICUT", group: "Wayanad Ooty", pickup: "Calicut", drop: "Mysore", days: 5, stay: "2 wayanad 2 ooty 0 mysore", pages: ["kerala", "south"], type: "family" },
    { id: "clt-wayanad-ooty-mysore-6d", sheet: "CALICUT", group: "Wayanad Ooty", pickup: "Calicut", drop: "Mysore", days: 6, stay: "2 wayanad 2 ooty 1 mysore", pages: ["kerala", "south"], type: "family" },
    { id: "clt-wayanad-coorg-ooty-7d", sheet: "CALICUT", group: "Grand circuit", pickup: "Calicut", drop: "Calicut or Mysore", days: 7, stay: "2 wayanad 2 coorg 2 ooty", pages: ["kerala", "south"], type: "family" },
    { id: "clt-grand-8d", sheet: "CALICUT", group: "Grand circuit", pickup: "Calicut", drop: "Mysore or Coimbatore", days: 8, stay: "2 wayanad 2 coorg 1 mysore 2 ooty", pages: ["kerala", "south"], type: "family" },

    // KOCHI — Munnar
    { id: "kochi-munnar-2d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 2, stay: "munnar", pages: ["kerala"], type: "leisure" },
    { id: "kochi-munnar-3d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 3, stay: "munnar", pages: ["kerala"], type: "couple" },
    { id: "kochi-munnar-4d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 4, stay: "munnar", pages: ["kerala"], type: "family" },
    { id: "kochi-munnar-thekkady-3d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 3, stay: "munnar thekkady", pages: ["kerala"], type: "family" },
    { id: "kochi-munnar-thekkady-4d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 4, stay: "munnar thekkady", pages: ["kerala"], type: "family" },
    { id: "kochi-munnar-athirappilly-3d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 3, stay: "munnar athirappilly", pages: ["kerala"], type: "adventure" },
    { id: "kochi-munnar-athirappilly-4d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 4, stay: "munnar athirappilly", pages: ["kerala"], type: "adventure" },
    { id: "kochi-munnar-kochi-3d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 3, stay: "munnar kochi", pages: ["kerala"], type: "leisure" },
    { id: "kochi-munnar-kochi-4d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 4, stay: "munnar kochi", pages: ["kerala"], type: "leisure" },
    { id: "kochi-alleppey-munnar-3d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 3, stay: "alleppey munnar", pages: ["kerala"], type: "couple" },
    { id: "kochi-alleppey-munnar-4d", sheet: "KOCHI", group: "Munnar", pickup: "Kochi", drop: "Kochi", days: 4, stay: "alleppey munnar", pages: ["kerala"], type: "couple" },

    // KOCHI — Vagamon / Thekkady
    { id: "kochi-vagamon-2d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 2, stay: "vagamon", pages: ["kerala"], type: "leisure" },
    { id: "kochi-vagamon-3d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 3, stay: "vagamon", pages: ["kerala"], type: "couple" },
    { id: "kochi-thekkady-2d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 2, stay: "thekkady", pages: ["kerala"], type: "adventure" },
    { id: "kochi-thekkady-gavi-3d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 3, stay: "thekkady gavi", pages: ["kerala"], type: "adventure" },
    { id: "kochi-vagamon-thekkady-3d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 3, stay: "vagamon thekkady", pages: ["kerala"], type: "adventure" },
    { id: "kochi-vagamon-thekkady-4d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 4, stay: "vagamon thekkady", pages: ["kerala"], type: "adventure" },
    { id: "kochi-vagamon-kochi-3d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 3, stay: "vagamon kochi", pages: ["kerala"], type: "leisure" },
    { id: "kochi-vagamon-kochi-4d", sheet: "KOCHI", group: "Vagamon Thekkady", pickup: "Kochi", drop: "Kochi", days: 4, stay: "vagamon kochi", pages: ["kerala"], type: "leisure" },

    // KOCHI — Varkala / Kovalam / TVM
    { id: "kochi-varkala-2d", sheet: "KOCHI", group: "Varkala Kovalam", pickup: "Kochi", drop: "Kochi", days: 2, stay: "1 varkala", pages: ["kerala"], type: "couple" },
    { id: "kochi-varkala-3d", sheet: "KOCHI", group: "Varkala Kovalam", pickup: "Kochi", drop: "Kochi", days: 3, stay: "2 varkala", pages: ["kerala"], type: "couple" },
    { id: "kochi-varkala-tvm-3d", sheet: "KOCHI", group: "Varkala Kovalam", pickup: "Kochi", drop: "Trivandrum", days: 3, stay: "varkala trivandrum", pages: ["kerala"], type: "leisure" },
    { id: "kochi-varkala-kovalam-3d", sheet: "KOCHI", group: "Varkala Kovalam", pickup: "Kochi", drop: "Trivandrum", days: 3, stay: "1 varkala 1 kovalam", pages: ["kerala"], type: "couple" },
    { id: "kochi-varkala-kochi-3d", sheet: "KOCHI", group: "Varkala Kovalam", pickup: "Kochi", drop: "Kochi", days: 3, stay: "varkala kochi", pages: ["kerala"], type: "leisure" },
    { id: "kochi-varkala-alleppey-4d", sheet: "KOCHI", group: "Varkala Kovalam", pickup: "Kochi", drop: "Kochi", days: 4, stay: "2 varkala 1 alleppey 0 kochi", pages: ["kerala"], type: "couple" },
    { id: "kochi-varkala-kovalam-tvm-4d", sheet: "KOCHI", group: "Varkala Kovalam", pickup: "Kochi", drop: "Trivandrum", days: 4, stay: "2 varkala 1 kovalam 0 trivandrum", pages: ["kerala"], type: "leisure" },

    // KOCHI — Combo MVV
    { id: "kochi-amv-5d", sheet: "KOCHI", group: "Combo MVV", pickup: "Kochi", drop: "Trivandrum", days: 5, stay: "alleppey munnar varkala", pages: ["kerala"], type: "family" },
    { id: "kochi-amv-6d", sheet: "KOCHI", group: "Combo MVV", pickup: "Kochi", drop: "Trivandrum", days: 6, stay: "alleppey munnar varkala", pages: ["kerala"], type: "family" },
    { id: "kochi-vagamon-varkala-4d", sheet: "KOCHI", group: "Combo MVV", pickup: "Kochi", drop: "Trivandrum", days: 4, stay: "2 vagamon 1 varkala", pages: ["kerala"], type: "couple" },
    { id: "kochi-alleppey-vagamon-varkala-5d", sheet: "KOCHI", group: "Combo MVV", pickup: "Kochi", drop: "Trivandrum", days: 5, stay: "1 alleppey 2 vagamon 1 varkala", pages: ["kerala"], type: "couple" },

    // KOCHI — Alleppey / Kochi / Athirappilly
    { id: "kochi-city-2d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 2, stay: "kochi", pages: ["kerala"], type: "leisure" },
    { id: "kochi-athirappilly-2d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 2, stay: "athirappilly", pages: ["kerala"], type: "adventure" },
    { id: "kochi-alleppey-2d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 2, stay: "alleppey", pages: ["kerala"], type: "couple" },
    { id: "kochi-alleppey-kochi-3d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 3, stay: "alleppey kochi", pages: ["kerala"], type: "couple" },
    { id: "kochi-alleppey-munnar-drop-4d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 4, stay: "1 alleppey 2 munnar 0 kochi", pages: ["kerala"], type: "family" },
    { id: "kochi-alleppey-munnar-kochi-5d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 5, stay: "1 alleppey 2 munnar 1 kochi", pages: ["kerala"], type: "family" },
    { id: "kochi-alleppey-vagamon-3d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 3, stay: "1 alleppey 1 vagamon", pages: ["kerala"], type: "couple" },
    { id: "kochi-alleppey-vagamon-4d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 4, stay: "1 alleppey 2 vagamon", pages: ["kerala"], type: "couple" },
    { id: "kochi-alleppey-vagamon-kochi-4d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 4, stay: "alleppey vagamon kochi", pages: ["kerala"], type: "leisure" },
    { id: "kochi-alleppey-vagamon-kochi-5d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 5, stay: "alleppey vagamon kochi", pages: ["kerala"], type: "leisure" },
    { id: "kochi-alleppey-vagamon-thekkady-4d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 4, stay: "alleppey vagamon thekkady", pages: ["kerala"], type: "adventure" },
    { id: "kochi-alleppey-vagamon-thekkady-5d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Kochi", days: 5, stay: "alleppey vagamon thekkady", pages: ["kerala"], type: "adventure" },
    { id: "kochi-alleppey-varkala-3d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Trivandrum", days: 3, stay: "1 alleppey 1 varkala", pages: ["kerala"], type: "couple" },
    { id: "kochi-alleppey-varkala-tvm-4d", sheet: "KOCHI", group: "Alleppey Kochi", pickup: "Kochi", drop: "Trivandrum", days: 4, stay: "1 alleppey 2 varkala 0 trivandrum", pages: ["kerala"], type: "couple" },

    // ALL Kerala
    { id: "allk-kochi-mta-5d", sheet: "ALL Kerala", group: "Classic circuit", pickup: "Kochi", drop: "Kochi", days: 5, stay: "2 munnar 1 thekkady 1 alleppey", pages: ["kerala"], type: "family" },
    { id: "allk-kochi-kmta-6d", sheet: "ALL Kerala", group: "Classic circuit", pickup: "Kochi", drop: "Kochi", days: 6, stay: "1 kochi 2 munnar 1 thekkady 1 alleppey", pages: ["kerala"], type: "family" },
    { id: "allk-kochi-kamta-7d", sheet: "ALL Kerala", group: "Classic circuit", pickup: "Kochi", drop: "Kochi", days: 7, stay: "1 kochi 1 athirappilly 2 munnar 1 thekkady 1 alleppey", pages: ["kerala"], type: "family" },
    { id: "allk-kochi-tvm-mav-5d", sheet: "ALL Kerala", group: "Kochi to TVM", pickup: "Kochi", drop: "Trivandrum", days: 5, stay: "2 munnar 1 alleppey 1 varkala", pages: ["kerala"], type: "couple" },
    { id: "allk-kochi-tvm-mav2-6d", sheet: "ALL Kerala", group: "Kochi to TVM", pickup: "Kochi", drop: "Trivandrum", days: 6, stay: "2 munnar 1 alleppey 2 varkala", pages: ["kerala"], type: "couple" },
    { id: "allk-kochi-tvm-mtav-6d", sheet: "ALL Kerala", group: "Kochi to TVM", pickup: "Kochi", drop: "Trivandrum", days: 6, stay: "2 munnar 1 thekkady 1 alleppey 1 varkala", pages: ["kerala"], type: "family" },
    { id: "allk-kochi-tvm-mtav2-7d", sheet: "ALL Kerala", group: "Kochi to TVM", pickup: "Kochi", drop: "Trivandrum", days: 7, stay: "2 munnar 1 thekkady 1 alleppey 2 varkala", pages: ["kerala"], type: "family" },
    { id: "allk-kochi-tvm-kmtav-8d", sheet: "ALL Kerala", group: "Kochi to TVM", pickup: "Kochi", drop: "Trivandrum", days: 8, stay: "1 kochi 2 munnar 1 thekkady 1 alleppey 2 varkala", pages: ["kerala"], type: "family" },
    { id: "allk-kochi-tvm-kmtavk-9d", sheet: "ALL Kerala", group: "Kochi to TVM", pickup: "Kochi", drop: "Trivandrum", days: 9, stay: "1 kochi 2 munnar 1 thekkady 1 alleppey 2 varkala 1 kovalam", pages: ["kerala"], type: "family" },
    { id: "allk-kochi-tvm-full-10d", sheet: "ALL Kerala", group: "Kochi to TVM", pickup: "Kochi", drop: "Trivandrum", days: 10, stay: "1 kochi 2 munnar 1 thekkady 1 alleppey 2 varkala 1 kovalam 1 trivandrum", pages: ["kerala"], type: "family" },
    { id: "allk-clt-kochi-wm-5d", sheet: "ALL Kerala", group: "Calicut to Kochi", pickup: "Calicut", drop: "Kochi", days: 5, stay: "2 wayanad 2 munnar", pages: ["kerala"], type: "family" },
    { id: "allk-clt-kochi-wam-6d", sheet: "ALL Kerala", group: "Calicut to Kochi", pickup: "Calicut", drop: "Kochi", days: 6, stay: "2 wayanad 1 athirappilly 2 munnar", pages: ["kerala"], type: "family" },
    { id: "allk-clt-kochi-wom-7d", sheet: "ALL Kerala", group: "Calicut to Kochi", pickup: "Calicut", drop: "Kochi", days: 7, stay: "2 wayanad 2 ooty 2 munnar", pages: ["kerala", "south"], type: "family" },

    // TN & KA
    { id: "tn-ooty-2d", sheet: "TN & KA PLANS", group: "Ooty", pickup: "Coimbatore", drop: "Coimbatore", days: 2, stay: "1 ooty", pages: ["south"], type: "leisure" },
    { id: "tn-ooty-3d", sheet: "TN & KA PLANS", group: "Ooty", pickup: "Coimbatore", drop: "Coimbatore", days: 3, stay: "2 ooty", pages: ["south"], type: "family" },
    { id: "tn-ooty-4d", sheet: "TN & KA PLANS", group: "Ooty", pickup: "Coimbatore", drop: "Coimbatore", days: 4, stay: "3 ooty", pages: ["south"], type: "family" },
    { id: "tn-coorg-2d", sheet: "TN & KA PLANS", group: "Coorg", pickup: "Mysore", drop: "Mysore", days: 2, stay: "1 coorg", pages: ["south"], type: "couple" },
    { id: "tn-coorg-chikmagalur-3d", sheet: "TN & KA PLANS", group: "Coorg", pickup: "Mysore", drop: "Mysore", days: 3, stay: "1 coorg 1 chikmagalur", pages: ["south"], type: "couple" },
    { id: "tn-mysore-coorg-3d", sheet: "TN & KA PLANS", group: "Coorg", pickup: "Mysore", drop: "Mysore", days: 3, stay: "mysore coorg", pages: ["south"], type: "heritage" },
    { id: "tn-mysore-coorg-chik-4d", sheet: "TN & KA PLANS", group: "Coorg", pickup: "Mysore", drop: "Mysore", days: 4, stay: "mysore coorg chikmagalur", pages: ["south"], type: "heritage" },
    { id: "tn-mysore-ooty-3d", sheet: "TN & KA PLANS", group: "Mysore Ooty", pickup: "Mysore", drop: "Coimbatore", days: 3, stay: "mysore ooty", pages: ["south"], type: "family" },
    { id: "tn-mysore-ooty-4d", sheet: "TN & KA PLANS", group: "Mysore Ooty", pickup: "Mysore", drop: "Coimbatore", days: 4, stay: "mysore ooty", pages: ["south"], type: "family" },
    { id: "tn-valparai-2d", sheet: "TN & KA PLANS", group: "Valparai", pickup: "Coimbatore", drop: "Coimbatore", days: 2, stay: "valparai", pages: ["south"], type: "adventure" },
    { id: "tn-valparai-athirappilly-3d", sheet: "TN & KA PLANS", group: "Valparai", pickup: "Coimbatore", drop: "Kochi", days: 3, stay: "valparai athirappilly", pages: ["south", "kerala"], type: "adventure" },
    { id: "tn-kodaikanal-2d", sheet: "TN & KA PLANS", group: "Kodaikanal", pickup: "Coimbatore", drop: "Coimbatore", days: 2, stay: "kodaikanal", pages: ["south"], type: "leisure" },
    { id: "tn-kodaikanal-3d", sheet: "TN & KA PLANS", group: "Kodaikanal", pickup: "Coimbatore", drop: "Coimbatore", days: 3, stay: "kodaikanal", pages: ["south"], type: "couple" },

    // Domestic
    { id: "domestic-lakshadweep-5d", sheet: "Domestic", group: "Lakshadweep", pickup: "Kochi", drop: "Kochi", days: 5, stay: "4 lakshadweep", pages: ["domestic"], type: "leisure" },
  ];

  function normCity(name) {
    var s = (name || "").toLowerCase();
    if (s.indexOf("calicut") !== -1 || s.indexOf("kozhikode") !== -1 || s.indexOf("clt") !== -1) return "calicut";
    if (s.indexOf("cochin") !== -1 || s.indexOf("kochi") !== -1) return "kochi";
    if (s.indexOf("coimbatore") !== -1 || s.indexOf("cbe") !== -1) return "coimbatore";
    if (s.indexOf("mysore") !== -1) return "mysore";
    if (s.indexOf("trivandrum") !== -1 || s === "tvm") return "trivandrum";
    return s.replace(/[^a-z]/g, "");
  }

  function pickupSlug(pickup) {
    var s = (pickup || "").toLowerCase();
    if (s.indexOf("calicut") !== -1 || s.indexOf("kozhikode") !== -1) return "calicut";
    if (s.indexOf("kochi") !== -1) return "kochi";
    if (s.indexOf("coimbatore") !== -1) return "coimbatore";
    if (s.indexOf("mysore") !== -1) return "mysore";
    if (s.indexOf("trivandrum") !== -1) return "trivandrum";
    return s.split(/[^a-z]+/)[0] || "";
  }

  function durationBucket(days) {
    if (days <= 4) return "2-4";
    if (days <= 7) return "5-7";
    return "8-10";
  }

  function stateFor(pkg, dests) {
    if (pkg.pages.indexOf("domestic") !== -1) return "lakshadweep";
    var southOnly = dests.filter(function (d) {
      return ["ooty", "coorg", "mysore", "chikmagalur", "valparai", "kodaikanal"].indexOf(d) !== -1;
    });
    var kerala = dests.filter(function (d) {
      return ["wayanad", "munnar", "alleppey", "thekkady", "kochi", "varkala", "vagamon", "athirappilly", "kovalam", "trivandrum", "kozhikode", "gavi"].indexOf(d) !== -1;
    });
    if (southOnly.length && !kerala.length) {
      if (dests.indexOf("coorg") !== -1 || dests.indexOf("mysore") !== -1 || dests.indexOf("chikmagalur") !== -1) return "karnataka";
      return "tamil-nadu";
    }
    return "kerala";
  }

  function normalizeStay(stay) {
    var s = (stay || "").toLowerCase().replace(/[-–]/g, " ").replace(/\s+/g, " ").trim();
    PLACE_ALIASES.forEach(function (pair) {
      s = s.split(pair[0]).join(pair[1]);
    });
    return s;
  }

  function parseStay(stay, days) {
    var s = normalizeStay(stay);
    var numbered = [];
    var re = /(\d+)\s+([a-z][a-z ]*?)(?=(?:\s+\d+\s+[a-z])|$)/g;
    var m;
    while ((m = re.exec(s))) {
      var key = matchPlace(m[2].trim());
      if (key) numbered.push({ place: key, nights: parseInt(m[1], 10) });
    }
    if (numbered.length) return numbered;

    var found = [];
    var rest = s;
    PLACE_KEYS.forEach(function (key) {
      var idx = rest.indexOf(key);
      if (idx === -1) return;
      found.push({ key: key, idx: idx });
      rest = rest.replace(key, " ".repeat(key.length));
    });
    found.sort(function (a, b) {
      return a.idx - b.idx;
    });
    var places = found.map(function (f) {
      return f.key;
    });
    if (!places.length) places = ["kochi"];

    var totalNights = Math.max(0, days - 1);
    if (places.length === 1) return [{ place: places[0], nights: totalNights }];

    var base = Math.floor(totalNights / places.length);
    var extra = totalNights % places.length;
    return places.map(function (p, i) {
      return { place: p, nights: base + (i < extra ? 1 : 0) };
    });
  }

  function matchPlace(chunk) {
    chunk = chunk.trim();
    for (var i = 0; i < PLACE_KEYS.length; i++) {
      if (chunk === PLACE_KEYS[i] || chunk.indexOf(PLACE_KEYS[i]) !== -1) return PLACE_KEYS[i];
    }
    return "";
  }

  function labelOf(place) {
    return (PLACES[place] && PLACES[place].label) || place;
  }

  function buildTitle(stays, days) {
    var names = [];
    stays.forEach(function (s) {
      if (s.nights <= 0) return;
      var lab = labelOf(s.place);
      if (names.indexOf(lab) === -1) names.push(lab);
    });
    if (!names.length) names = stays.map(function (s) { return labelOf(s.place); });
    var dest =
      names.length <= 2 ? names.join(" & ") : names.slice(0, -1).join(", ") + " & " + names[names.length - 1];
    return dest + " · " + days + " Days";
  }

  function buildItinerary(pkg, stays) {
    var days = [];
    var pickup = pkg.pickup.split(" or ")[0];
    var drop = pkg.drop.split(" or ")[0];

    function push(title, text) {
      days.push({ day: days.length + 1, title: title, text: text });
    }

    var first = stays[0];
    var firstLabel = labelOf(first.place);
    var sameStart = normCity(pickup) === first.place || (pickup === "Calicut" && first.place === "kozhikode");

    if (sameStart) {
      push("Arrive " + pickup + " · " + firstLabel, (PLACES[first.place] && PLACES[first.place].arrive) || "Arrive, check in and spend the evening at leisure.");
    } else {
      push(
        "Arrive " + pickup + " · Drive to " + firstLabel,
        "Meet our representative at " + pickup + ". " + ((PLACES[first.place] && PLACES[first.place].arrive) || "Drive to your first stay and check in.")
      );
    }

    var i;
    for (i = 1; i < first.nights; i++) {
      push(firstLabel + " sightseeing", (PLACES[first.place] && PLACES[first.place].sightseeing) || "Full day of sightseeing. Evening at leisure.");
    }

    for (var s = 1; s < stays.length; s++) {
      var stay = stays[s];
      var lab = labelOf(stay.place);
      var prev = labelOf(stays[s - 1].place);
      if (stay.nights === 0) {
        push(
          prev + " to " + lab + " · Departure",
          "Check out and drive to " + lab + " for drop. No overnight stay here. Onward journey as per your schedule."
        );
        continue;
      }
      push(
        prev + " to " + lab,
        "Check out after breakfast and drive to " + lab + ". " + ((PLACES[stay.place] && PLACES[stay.place].arrive) || "Check in and evening at leisure.")
      );
      for (i = 1; i < stay.nights; i++) {
        push(lab + " sightseeing", (PLACES[stay.place] && PLACES[stay.place].sightseeing) || "Sightseeing and leisure time.");
      }
    }

    var last = stays[stays.length - 1];
    if (last.nights > 0) {
      if (normCity(drop) === last.place || drop.toLowerCase().indexOf(labelOf(last.place).toLowerCase()) !== -1) {
        push("Depart " + drop, "Breakfast and check out. Transfer to your drop point in " + drop + " for onward travel.");
      } else {
        push(
          "Drive to " + drop + " · Departure",
          "Check out and drive to " + drop + " for drop and onward travel. Share your flight or train time when you enquire so we can pace the last morning."
        );
      }
    }

    while (days.length < pkg.days) {
      var insertAt = Math.max(1, days.length - 1);
      var stayRef = stays[0];
      for (i = 0; i < stays.length; i++) {
        if (stays[i].nights > 0) stayRef = stays[i];
      }
      days.splice(insertAt, 0, {
        day: 0,
        title: labelOf(stayRef.place) + " at leisure",
        text: "An extra unhurried day for rest, optional activities or a repeat of favourite viewpoints. We confirm the pacing when you enquire.",
      });
    }
    while (days.length > pkg.days) {
      var removed = false;
      for (i = 1; i < days.length - 1; i++) {
        if (/sightseeing|leisure/i.test(days[i].title)) {
          days.splice(i, 1);
          removed = true;
          break;
        }
      }
      if (!removed) days.splice(Math.max(1, days.length - 2), 1);
    }
    days.forEach(function (d, idx) {
      d.day = idx + 1;
    });
    return days;
  }

  function expand(raw) {
    var stays = parseStay(raw.stay, raw.days);
    var dests = [];
    stays.forEach(function (s) {
      if (dests.indexOf(s.place) === -1) dests.push(s.place);
    });
    var overnight = stays.filter(function (s) {
      return s.nights > 0;
    });
    var nights = overnight.reduce(function (sum, s) {
      return sum + s.nights;
    }, 0);
    if (!nights) nights = Math.max(0, raw.days - 1);

    var title = buildTitle(stays, raw.days);
    var destLine = overnight.map(function (s) {
      return labelOf(s.place);
    }).filter(function (v, i, arr) {
      return arr.indexOf(v) === i;
    }).join(" · ");

    var highlights = [];
    overnight.concat(stays).forEach(function (s) {
      var tags = (PLACES[s.place] && PLACES[s.place].tags) || [];
      tags.forEach(function (t) {
        if (highlights.indexOf(t) === -1) highlights.push(t);
      });
    });
    if (highlights.indexOf("Private cab") === -1) highlights.push("Private cab");
    highlights = highlights.slice(0, 5);

    var hasHouseboat = overnight.some(function (s) {
      return s.place === "alleppey";
    });

    var overview =
      "A " +
      raw.days +
      "-day journey from " +
      raw.pickup +
      " to " +
      raw.drop +
      ", staying in " +
      destLine +
      ". Designed with unhurried transfers and time to actually see each place. Share your dates and group size — we will confirm stays and send personalised pricing.";

    var cardText =
      destLine +
      " from " +
      raw.pickup +
      ", finishing at " +
      raw.drop +
      ". Request pricing for your dates.";

    var image = "packages/" + raw.id + ".jpg";
    var gallery = [image].concat(
      pickGallery(dests, raw.type, "", raw.id).filter(function (img) {
        return img && img.indexOf("packages/") !== 0;
      }).slice(0, 3)
    );

    var staySummary = overnight
      .map(function (s) {
        return s.nights + " night" + (s.nights === 1 ? "" : "s") + " in " + labelOf(s.place);
      })
      .join(", ");

    return {
      id: raw.id,
      sheet: raw.sheet,
      group: raw.group,
      title: title,
      pickup: raw.pickup,
      drop: raw.drop,
      pickupSlug: pickupSlug(raw.pickup),
      days: raw.days,
      nights: nights,
      staySplit: raw.stay,
      stays: stays,
      destinations: dests,
      destLine: destLine,
      pages: raw.pages,
      type: raw.type,
      state: stateFor(raw, dests),
      duration: durationBucket(raw.days),
      image: image,
      gallery: gallery.slice(0, 4),
      overview: overview,
      cardText: cardText,
      highlights: highlights,
      itinerary: buildItinerary(raw, stays),
      hasHouseboat: hasHouseboat,
      staySummary: staySummary,
      accommodation:
        "Comfortable hotels / resorts as per the stay split (" +
        staySummary +
        "). Specific properties are confirmed based on availability for your dates." +
        (hasHouseboat ? " Alleppey includes an overnight houseboat stay with meals on board as applicable." : ""),
    };
  }

  var ALL = RAW.map(expand);

  window.YNPackages = {
    all: ALL,
    places: PLACES,
    byId: function (id) {
      for (var i = 0; i < ALL.length; i++) {
        if (ALL[i].id === id) return ALL[i];
      }
      return null;
    },
    forPage: function (page) {
      return ALL.filter(function (p) {
        return p.pages.indexOf(page) !== -1;
      });
    },
    related: function (pkg, n) {
      n = n || 3;
      var scored = ALL.filter(function (p) {
        return p.id !== pkg.id;
      }).map(function (p) {
        var score = 0;
        if (p.group === pkg.group) score += 5;
        pkg.destinations.forEach(function (d) {
          if (p.destinations.indexOf(d) !== -1) score += 2;
        });
        if (p.pickupSlug === pkg.pickupSlug) score += 1;
        return { p: p, score: score };
      });
      scored.sort(function (a, b) {
        return b.score - a.score;
      });
      return scored.slice(0, n).map(function (x) {
        return x.p;
      });
    },
  };
})();
