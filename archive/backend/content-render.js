const rwEsc = (value) => String(value ?? "").replace(/[&<>"']/g, (c) => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const rwImage = (src) => src && src.startsWith("/") ? `..${src}` : src;
const rwArticle = (item) => `../article.php?section=${encodeURIComponent(item.section)}&slug=${encodeURIComponent(item.slug)}`;
async function loadRideWireContent(section) {
  try {
    const response = await fetch(`../api/content.php?section=${encodeURIComponent(section)}`, { credentials: "omit", headers: { Accept: "application/json" } });
    if (!response.ok) return;
    const items = await response.json();
    if (!items.length) {
      const emptyTarget = section === "news" ? document.querySelector(".scene-news-grid") : section === "spotlight" ? document.querySelector(".spotlight-card-grid") : document.querySelector("[data-rider-grid]");
      emptyTarget?.replaceChildren();
      return;
    }
    if (section === "news") {
      const grid = document.querySelector(".scene-news-grid"); if (!grid) return;
      grid.replaceChildren(...items.map((item, index) => {
        const card = document.createElement("article"); card.className = `scene-news-card${(index + 1) % 3 === 0 ? " scene-news-card-featured" : ""}`; card.dataset.newsCategory = item.discipline === "racing" ? "racing" : "freestyle";
        const figure = document.createElement("figure"); figure.className = "scene-news-art"; const image = document.createElement("img"); image.className = "scene-news-photo"; image.src = rwImage(item.image); image.alt = item.image_alt || item.title; image.loading = "lazy"; figure.append(image);
        const label = document.createElement("span"); label.textContent = item.category || "RIDEWIRE BMX"; const graphic = document.createElement("b"); graphic.textContent = item.title.toUpperCase(); const caption = document.createElement("figcaption"); caption.textContent = item.image_caption || ""; figure.append(label, graphic, caption);
        const copy = document.createElement("div"); copy.className = "scene-news-copy"; const kicker = document.createElement("p"); kicker.className = "card-kicker"; kicker.textContent = `${item.category || "BMX NEWS"} · ${item.published_at || ""}`; const title = document.createElement("h3"); title.textContent = item.title; const summary = document.createElement("p"); summary.textContent = item.summary; const link = document.createElement("a"); link.className = "text-link"; link.href = rwArticle(item); link.textContent = "Read full RideWire article →"; copy.append(kicker,title,summary,link); card.append(figure,copy); return card;
      }));
      const activeFilter = document.querySelector('[data-news-filter][aria-pressed="true"]')?.dataset.newsFilter || "all";
      grid.querySelectorAll("[data-news-category]").forEach((card) => { card.hidden = activeFilter !== "all" && card.dataset.newsCategory !== activeFilter; });
    } else if (section === "spotlight") {
      const grid = document.querySelector(".spotlight-card-grid"); if (!grid) return;
      grid.replaceChildren(...items.map((item) => { const card=document.createElement("a");card.className="spotlight-rider-card";card.href=rwArticle(item);const img=document.createElement("img");img.src=rwImage(item.image);img.alt=item.image_alt||item.title;img.loading="lazy";const copy=document.createElement("div");copy.className="spotlight-rider-card-copy";const kicker=document.createElement("p");kicker.className="card-kicker";kicker.textContent=item.category||"RIDER FEATURE";const name=document.createElement("h2");name.textContent=item.title;const summary=document.createElement("p");summary.textContent=item.summary;const cta=document.createElement("span");cta.className="spotlight-rider-card-link";cta.textContent=`Read ${item.title}’s story →`;copy.append(kicker,name,summary,cta);card.append(img,copy);return card;}));
    } else if (section === "rider") {
      const grid=document.querySelector("[data-rider-grid]");if(!grid)return;
      grid.replaceChildren(...items.map((item)=>{const card=document.createElement("article");card.className="rider-database-card";card.dataset.discipline=(item.discipline||"park").toLowerCase().split(/[ ·,]/)[0];card.dataset.search=`${item.title} ${item.country} ${item.discipline} ${item.highlight}`.toLowerCase();const figure=document.createElement("figure");figure.className="rider-database-photo";const image=document.createElement("img");image.src=rwImage(item.image);image.alt=item.image_alt||item.title;image.loading="lazy";const credit=document.createElement("figcaption");credit.textContent=item.image_caption||"";figure.append(image,credit);const copy=document.createElement("div");copy.className="rider-database-copy";const meta=document.createElement("div");meta.className="rider-card-meta";const country=document.createElement("span");country.textContent=item.country||"BMX";const discipline=document.createElement("span");discipline.textContent=item.discipline||"Freestyle";meta.append(country,discipline);const name=document.createElement("h2");name.textContent=item.title;const summary=document.createElement("p");summary.textContent=item.summary;const highlight=document.createElement("p");highlight.className="rider-highlight";highlight.textContent=item.highlight;const link=document.createElement("a");link.className="rider-card-link";link.href=rwArticle(item);link.textContent="Read rider profile →";copy.append(meta,name,summary,highlight,link);card.append(figure,copy);return card;}));
      grid.querySelectorAll(".rider-database-card").forEach((card,index)=>{
        const item=items[index];const original=card.querySelector(".rider-card-link");const button=document.createElement("button");button.type="button";button.className="rider-card-link";button.textContent="Read rider bio →";original?.replaceWith(button);
        button.addEventListener("click",()=>{const dialog=document.querySelector("#rider-bio-dialog");if(!dialog)return;dialog.querySelector("[data-rider-bio-name]").textContent=item.title;dialog.querySelector("[data-rider-bio-meta]").textContent=[item.country,item.discipline].filter(Boolean).join(" / ");dialog.querySelector("[data-rider-bio-highlight]").textContent=item.highlight||"";dialog.querySelector("[data-rider-bio-text]").textContent=item.summary||"";const bioImage=dialog.querySelector("[data-rider-bio-image]");bioImage.src=rwImage(item.image);bioImage.alt=item.image_alt||item.title;dialog.querySelector("[data-rider-bio-credit]").textContent=item.image_caption||"";const source=dialog.querySelector("[data-rider-bio-source]");source.href=rwArticle(item);source.firstChild.textContent="Open RideWire profile ";source.hidden=false;dialog.showModal();});
      });
      document.querySelectorAll("[data-rider-results]").forEach((el)=>el.textContent=`Showing ${items.length} rider ${items.length===1?"profile":"profiles"}`);
      document.querySelector("#rider-search")?.dispatchEvent(new Event("input",{bubbles:true}));
    }
  } catch (error) { /* Static page cards remain available if the PHP content feed is offline. */ }
}
const path = location.pathname.toLowerCase();
if (path.endsWith("/news.html")) loadRideWireContent("news");
if (path.endsWith("/spotlight.html")) loadRideWireContent("spotlight");
if (path.endsWith("/riders.html")) loadRideWireContent("rider");
