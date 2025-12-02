<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Mobile Showcase — Search & Add</title>
  <style>
    :root{
      --bg:#f6f8fb; --card:#ffffff; --accent:#0b75d1; --muted:#6b7280;
    }
    body{font-family:Inter, system-ui, Arial; margin:0; background:var(--bg); color:#111;}
    .container{max-width:1100px; margin:32px auto; padding:20px;}
    header{display:flex; gap:16px; align-items:center; justify-content:space-between;}
    h1{margin:0; font-size:1.25rem;}
    .controls{display:flex; gap:8px; align-items:center;}
    input[type="search"], input[type="text"]{
      padding:10px 12px; border-radius:8px; border:1px solid #e2e8f0; outline:none;
      min-width:220px; font-size:0.95rem; background:white;
    }
    button{background:var(--accent); color:white; border:0; padding:10px 12px; border-radius:8px; cursor:pointer;}
    button.secondary{background:#e6eef9; color:var(--accent);}
    .grid{display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; margin-top:22px;}
    .card{background:var(--card); border-radius:12px; padding:14px; box-shadow:0 6px 18px rgba(15,23,42,0.06);
         display:flex; gap:12px; align-items:center; transition:transform .18s, box-shadow .18s; border:1px solid transparent;}
    .card:hover{transform:translateY(-4px); box-shadow:0 12px 30px rgba(15,23,42,0.08);}
    .thumb{width:72px; height:72px; border-radius:8px; flex-shrink:0; background:#f0f3f7; display:flex; align-items:center; justify-content:center; font-weight:600; color:var(--muted);}
    .info{flex:1; min-width:0;}
    .name{font-weight:600; margin:0 0 6px 0; font-size:1rem;}
    .series{font-size:0.9rem; color:var(--muted); margin:0;}
    .meta{display:flex; gap:8px; margin-top:8px; align-items:center;}
    .badge{font-size:0.8rem; border-radius:999px; padding:4px 8px; background:#eef6ff; color:var(--accent);}
    .found{outline:4px solid rgba(11,117,209,0.12); box-shadow:0 8px 30px rgba(11,117,209,0.06);}
    .form-row{display:flex; gap:8px; margin-top:12px; align-items:center;}
    @media (max-width:520px){ .controls{flex-direction:column; align-items:stretch;} .form-row{flex-direction:column;} input[type="search"]{min-width:unset; width:100%}}
    .no-results{margin-top:24px; color:var(--muted);}
    .index-label{font-size:0.85rem; color:var(--muted);}
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div>
        <h1>Mobile Showcase</h1>
        <div class="index-label">Type a mobile name and press <strong>Search</strong> — it will highlight where that phone is.</div>
      </div>

      <div class="controls">
        <input id="searchInput" type="search" placeholder="Search mobile (e.g., Vivo Y33s)" />
        <button id="searchBtn">Search</button>
        <button id="clearBtn" class="secondary">Clear</button>
      </div>
    </header>

    <section style="margin-top:16px;">
      <div style="display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
        <div style="display:flex; gap:8px; align-items:center;">
          <input id="addName" type="text" placeholder="Mobile name (e.g., Vivo Y33s)" />
          <input id="addSeries" type="text" placeholder="Series (e.g., Y series)" />
          <input id="addImage" type="text" placeholder="Image URL (optional)" />
          <button id="addBtn">Add Mobile</button>
        </div>
        <div style="color:var(--muted); font-size:0.9rem;">Stored locally in this browser (localStorage)</div>
      </div>
    </section>

    <div id="grid" class="grid" aria-live="polite"></div>
    <div id="noResults" class="no-results" style="display:none;">No mobiles found. Try adding one or change search.</div>
  </div>

  <script>
    // initial sample data (common Vivo series examples)
    const defaultMobiles = [
      {name: "Vivo X90", series: "X", img: ""},
      {name: "Vivo X90 Pro", series: "X", img: ""},
      {name: "Vivo V29", series: "V", img: ""},
      {name: "Vivo Y33s", series: "Y", img: ""},
      {name: "Vivo Y21T", series: "Y", img: ""},
      {name: "Vivo S19", series: "S", img: ""},
      {name: "Vivo T1", series: "T", img: ""},
      {name: "Vivo Z1 Pro", series: "Z", img: ""}
    ];

    // read from localStorage or use defaults
    const STORAGE_KEY = "mobile_showcase_data_v1";
    let mobiles = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null") || defaultMobiles.slice();

    const grid = document.getElementById("grid");
    const noResults = document.getElementById("noResults");
    const searchInput = document.getElementById("searchInput");
    const searchBtn = document.getElementById("searchBtn");
    const clearBtn = document.getElementById("clearBtn");
    const addBtn = document.getElementById("addBtn");
    const addName = document.getElementById("addName");
    const addSeries = document.getElementById("addSeries");
    const addImage = document.getElementById("addImage");

    function save(){
      localStorage.setItem(STORAGE_KEY, JSON.stringify(mobiles));
    }

    function render(list = mobiles){
      grid.innerHTML = "";
      if(!list.length){
        noResults.style.display = "block";
        return;
      } else noResults.style.display = "none";

      list.forEach((m, idx) => {
        const card = document.createElement("div");
        card.className = "card";
        card.dataset.index = idx;

        const thumb = document.createElement("div");
        thumb.className = "thumb";
        if(m.img){
          const img = document.createElement("img");
          img.src = m.img;
          img.alt = m.name;
          img.style.width = "100%";
          img.style.height = "100%";
          img.style.objectFit = "cover";
          img.style.borderRadius = "8px";
          thumb.innerHTML = "";
          thumb.appendChild(img);
        } else {
          // initials
          const initials = m.name.split(" ").map(s => s[0]).slice(0,2).join("").toUpperCase();
          thumb.textContent = initials;
        }

        const info = document.createElement("div");
        info.className = "info";
        const n = document.createElement("p");
        n.className = "name";
        n.textContent = m.name;
        const s = document.createElement("p");
        s.className = "series";
        s.textContent = m.series || "Unknown series";

        const meta = document.createElement("div");
        meta.className = "meta";
        const badge = document.createElement("div");
        badge.className = "badge";
        badge.textContent = `#${idx+1}`;
        const locate = document.createElement("div");
        locate.style.fontSize = "0.85rem";
        locate.style.color = "var(--muted)";
        locate.textContent = `Stored locally`;

        meta.appendChild(badge);
        meta.appendChild(locate);

        info.appendChild(n);
        info.appendChild(s);
        info.appendChild(meta);

        card.appendChild(thumb);
        card.appendChild(info);

        grid.appendChild(card);
      });
    }

    // search and highlight first matching card (case-insensitive substring)
    function doSearch(query){
      // clear prior highlights
      document.querySelectorAll(".card").forEach(c => c.classList.remove("found"));
      if(!query || !query.trim()){
        render();
        return;
      }
      const q = query.trim().toLowerCase();
      const matches = [];
      mobiles.forEach((m, idx) => {
        if(m.name.toLowerCase().includes(q) || (m.series && m.series.toLowerCase().includes(q))){
          matches.push({m, idx});
        }
      });

      if(matches.length === 0){
        render([]); // show no results
        return;
      }

      // render full list but highlight matches; scroll to first
      render(mobiles);
      // highlight
      const firstIdx = matches[0].idx;
      const cardToHighlight = document.querySelector(`.card[data-index="${firstIdx}"]`);
      if(cardToHighlight){
        cardToHighlight.classList.add("found");
        // smooth scroll into view
        cardToHighlight.scrollIntoView({behavior:"smooth", block:"center"});
        // small pulse animation (temporary)
        setTimeout(()=> cardToHighlight.classList.remove("found"), 6500);
      }

      // also subtly mark all matches by a thin border
      matches.forEach(match=> {
        const c = document.querySelector(`.card[data-index="${match.idx}"]`);
        if(c && match.idx !== firstIdx) c.style.border = "1px dashed rgba(11,117,209,0.08)";
      });
    }

    // Add mobile
    addBtn.addEventListener("click", e => {
      const name = addName.value.trim();
      if(!name){ alert("Please enter mobile name"); addName.focus(); return; }
      const series = addSeries.value.trim() || "Unknown";
      const img = addImage.value.trim() || "";
      mobiles.push({name, series, img});
      save();
      render(mobiles);
      addName.value = ""; addSeries.value = ""; addImage.value = "";
      searchInput.value = ""; // clear search
      // scroll to new item
      setTimeout(()=> {
        const newCard = document.querySelector(`.card[data-index="${mobiles.length-1}"]`);
        if(newCard){
          newCard.classList.add("found");
          newCard.scrollIntoView({behavior:"smooth", block:"center"});
          setTimeout(()=> newCard.classList.remove("found"), 4000);
        }
      }, 120);
    });

    // Search handling
    searchBtn.addEventListener("click", ()=> doSearch(searchInput.value));
    searchInput.addEventListener("keydown", e => {
      if(e.key === "Enter") doSearch(searchInput.value);
    });

    clearBtn.addEventListener("click", ()=>{
      searchInput.value = "";
      // remove temporary dashed borders
      document.querySelectorAll(".card").forEach(c => { c.style.border = "1px solid transparent"; c.classList.remove("found"); });
      render(mobiles);
    });

    // initial render
    render(mobiles);

    // Expose small helper on window for debugging/quick find
    window.findMobile = (q) => {
      searchInput.value = q;
      doSearch(q);
    };
  </script>
</body>
</html>
