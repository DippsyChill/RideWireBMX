const menuToggle = document.querySelector('.menu-toggle');
const siteNav = document.querySelector('#site-nav');

// First-party aggregate page counts only; the endpoint stores day, path and count.
if (!location.pathname.startsWith('/staff/') && !location.pathname.startsWith('/api/')) {
  let pagePath = location.pathname;
  if (location.pathname === '/article.php') {
    const query = new URLSearchParams(location.search);
    const section = query.get('section') || '';
    const slug = query.get('slug') || '';
    if (/^(news|spotlight|rider)$/.test(section) && /^[a-z0-9-]{1,180}$/.test(slug)) pagePath = `/articles/${section}/${slug}`;
  }
  fetch('/api/track.php', { method: 'POST', credentials: 'omit', keepalive: true, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ path: pagePath }) }).catch(() => {});
}

if (menuToggle && siteNav) {
  menuToggle.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Open navigation' : 'Close navigation');
    siteNav.classList.toggle('is-open', !isOpen);
  });

  siteNav.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Open navigation');
      siteNav.classList.remove('is-open');
    });
  });
}

const yearDisplay = document.querySelector('#year');
if (yearDisplay) yearDisplay.textContent = new Date().getFullYear();

const newsFilterButtons = document.querySelectorAll("[data-news-filter]");
const newsCategoryCards = document.querySelectorAll("[data-news-category]");
const updateNewsCardRhythm = () => {
  let visibleIndex = 0;
  document.querySelectorAll("[data-news-category]").forEach((card) => {
    if (card.hidden) {
      card.classList.remove("scene-news-card-featured");
      return;
    }
    visibleIndex += 1;
    card.classList.toggle("scene-news-card-featured", visibleIndex % 3 === 0);
  });
};
newsFilterButtons.forEach((button) => button.addEventListener("click", () => {
  const filter = button.dataset.newsFilter;
  newsFilterButtons.forEach((item) => item.setAttribute("aria-pressed", String(item === button)));
  document.querySelectorAll("[data-news-category]").forEach((card) => { card.hidden = filter !== "all" && card.dataset.newsCategory !== filter; });
  updateNewsCardRhythm();
}));
updateNewsCardRhythm();

const spotlightDialog = document.querySelector("#spotlight-dialog");
const spotlightOpenButtons = document.querySelectorAll("[data-open-spotlight]");
const spotlightClose = document.querySelector("[data-close-spotlight]");

if (spotlightDialog && spotlightOpenButtons.length && spotlightClose) {
  spotlightOpenButtons.forEach((button) => {
    button.addEventListener("click", () => spotlightDialog.showModal());
  });
  spotlightClose.addEventListener("click", () => spotlightDialog.close());
  spotlightDialog.addEventListener("click", (event) => {
    if (event.target === spotlightDialog) spotlightDialog.close();
  });
}

const throwbackCarousel = document.querySelector("[data-throwback-slideshow]");
if (throwbackCarousel) {
  const throwbackSlides = Array.from(throwbackCarousel.querySelectorAll("[data-throwback-slide]"));
  const throwbackIndicators = Array.from(document.querySelectorAll("[data-throwback-to]"));
  const throwbackStatus = document.querySelector("[data-throwback-status]");
  let activeThrowback = 0;
  let throwbackTimer;

  const showThrowback = (index) => {
    activeThrowback = (index + throwbackSlides.length) % throwbackSlides.length;
    throwbackSlides.forEach((slide, slideIndex) => {
      const active = slideIndex === activeThrowback;
      slide.classList.toggle("is-active", active);
      slide.setAttribute("aria-hidden", String(!active));
    });
    throwbackIndicators.forEach((indicator, indicatorIndex) => {
      indicator.setAttribute("aria-pressed", String(indicatorIndex === activeThrowback));
    });
    if (throwbackStatus) {
      throwbackStatus.textContent = `${String(activeThrowback + 1).padStart(2, "0")} / ${String(throwbackSlides.length).padStart(2, "0")}`;
    }
  };

  const stopThrowbackTimer = () => window.clearInterval(throwbackTimer);
  const startThrowbackTimer = () => {
    stopThrowbackTimer();
    if (throwbackSlides.length > 1 && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      throwbackTimer = window.setInterval(() => showThrowback(activeThrowback + 1), 6500);
    }
  };

  document.querySelector("[data-throwback-prev]")?.addEventListener("click", () => {
    showThrowback(activeThrowback - 1);
    startThrowbackTimer();
  });
  document.querySelector("[data-throwback-next]")?.addEventListener("click", () => {
    showThrowback(activeThrowback + 1);
    startThrowbackTimer();
  });
  throwbackIndicators.forEach((indicator) => {
    indicator.addEventListener("click", () => {
      showThrowback(Number(indicator.dataset.throwbackTo));
      startThrowbackTimer();
    });
  });
  throwbackCarousel.addEventListener("mouseenter", stopThrowbackTimer);
  throwbackCarousel.addEventListener("mouseleave", startThrowbackTimer);
  throwbackCarousel.addEventListener("focusin", stopThrowbackTimer);
  throwbackCarousel.addEventListener("focusout", (event) => {
    if (!throwbackCarousel.contains(event.relatedTarget)) startThrowbackTimer();
  });
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) stopThrowbackTimer();
    else startThrowbackTimer();
  });
  startThrowbackTimer();
}

const riderGrid = document.querySelector("[data-rider-grid]");
if (riderGrid) {
  const riderCards = Array.from(riderGrid.querySelectorAll("[data-discipline]"));
  const riderSearch = document.querySelector("#rider-search");
  const riderFilters = Array.from(document.querySelectorAll("[data-rider-filter]"));
  const riderResults = document.querySelector("[data-rider-results]");
  const riderEmpty = document.querySelector("[data-rider-empty]");
  let activeRiderFilter = "all";

  const updateRiderCards = () => {
    const query = (riderSearch?.value ?? "").trim().toLowerCase();
    let visibleCount = 0;
    const liveCards = Array.from(riderGrid.querySelectorAll("[data-discipline]"));
    liveCards.forEach((card) => {
      const matchesDiscipline = activeRiderFilter === "all" || card.dataset.discipline === activeRiderFilter;
      const matchesQuery = !query || card.dataset.search.includes(query);
      const visible = matchesDiscipline && matchesQuery;
      card.hidden = !visible;
      if (visible) visibleCount += 1;
    });
    riderResults.textContent = `Showing ${visibleCount} rider ${visibleCount === 1 ? "profile" : "profiles"}`;
    if (riderEmpty) riderEmpty.hidden = visibleCount !== 0;
  };

  riderSearch?.addEventListener("input", updateRiderCards);
  riderFilters.forEach((filter) => filter.addEventListener("click", () => {
    activeRiderFilter = filter.dataset.riderFilter;
    riderFilters.forEach((button) => button.setAttribute("aria-pressed", String(button === filter)));
    updateRiderCards();
  }));

  const riderBios = {
    "Lucas Silva": "Lucas Silva is one of the UK’s promising young Park riders. After taking the Under-12 overall title in the 2024 Backyard Jam National Series, he stepped into the Under-15 category and earned a third-place finish at the 2025 Lloyds BMX Freestyle National Championships. His riding continues to develop through competitions and time on the bike.",
    "Alexandre Rodrigues": "Alexandre Rodrigues has spent more than twenty years progressing as a BMX rider in Portimão, Portugal. Known for technical riding, precise bike control and creative lines, he has competed around Portugal and represented LegendBikes and Vans. His story is rooted in the friendships, daily sessions and lasting passion built at his local park.",
    "Logan Martin": "Australian rider Logan Martin is known for precise, high-difficulty Park runs and a long list of contest wins. He became the first men’s BMX Freestyle Olympic champion at Tokyo 2020 and has since added more UCI World Championship success, continuing to shape the level of modern Park riding.",
    "Hannah Roberts": "Hannah Roberts has been one of the leading riders in women’s BMX Freestyle Park since the discipline’s early international contests. She won Olympic silver at Tokyo 2020 and has collected six UCI World Championship titles, combining technical consistency with bold trick selection.",
    "Charlotte Worthington": "Charlotte Worthington made BMX Freestyle history at Tokyo 2020, winning the first Olympic women’s Park title. Her final included a landmark 360 backflip, a trick that showed the progression and ambition she brought to the sport’s biggest stage.",
    "José “Maligno” Torres Gil": "Argentina’s José “Maligno” Torres Gil won the men’s BMX Freestyle Park title at Paris 2024 with a standout final run. The Olympic gold made him Argentina’s first Olympic champion in the discipline, following years of competing on the international Park circuit.",
    "Nikita Ducarroz": "Swiss-American rider Nikita Ducarroz earned bronze in the first Olympic women’s BMX Freestyle Park contest at Tokyo 2020. Her measured, technical style and persistence helped establish her as one of the discipline’s most recognisable international competitors.",
    "Matthias Dandois": "French Flatland rider Matthias Dandois built a signature style around intricate combinations, balance and smooth links between tricks. A ten-time UCI world champion, he also made his mark at jam events including Rebel Jam, where his creativity and style stood out.",
    "Drew Bezanson": "Canadian Park rider Drew Bezanson is celebrated for creative lines, huge transfers and technical riding. He won Simple Session four times and made finals at X Games and Dew Tour, building a reputation for turning difficult course features into memorable runs.",
    "Dave Mirra": "Dave Mirra was one of BMX Freestyle’s most influential competitors, helping bring the sport to a worldwide audience through X Games success and landmark video parts. He earned 24 X Games medals across BMX and RallyCross, including 14 gold medals, and remains a major figure in the sport’s history.",
    "Ryan “R-Willy” Williams": "Australian rider Ryan “R-Willy” Williams competes in both BMX and scooter, with a focus on big air and progression. At Nitro World Games he won BMX Best Trick in back-to-back years, and he has also earned X Games medals on a BMX bike.",
    "Garrett Reynolds": "New Jersey’s Garrett Reynolds became one of street BMX’s most decorated riders through his technical precision, consistency and distinctive approach to difficult spots. He was one of four riders followed in Nike 6.0’s 2008 film Writing on the Wall and has since earned a long list of X Games street wins.",
    "Dennis Enarson": "Dennis Enarson is a versatile American rider whose riding spans street, park and dirt. His early appearance in Writing on the Wall introduced his creative, high-impact style to a wide audience; he went on to become a regular at major contests and a central figure in modern BMX video projects.",
    "Nigel Sylvester": "New York rider Nigel Sylvester brought the city’s street culture and his own expressive style to Writing on the Wall. He later built the GO! film series around riding through cities and meeting people along the way, giving BMX a cinematic travel format with broad cultural reach.",
    "Mike Spinner": "Mike Spinner made a name for himself with technical combinations and fast, inventive trick sequences. A rider in Nike 6.0’s Writing on the Wall, he also competed at events including Dew Tour and the Nike 6.0 BMX Open. His tailwhip riding remains a memorable part of that era.",
    "Mike Miller": "British rider Mike Miller grew up riding trails in Tadley, Hampshire, then developed into a creative street and park rider. He turned professional after early contest success and became known for laid-back, stylish video parts with Proper and Ride UK. He also placed fifth in Style in the Park at the 2011 Vans Rebel Jam."
  };
  const riderBioDialog = document.querySelector("#rider-bio-dialog");
  if (riderBioDialog) {
    const bioImage = riderBioDialog.querySelector("[data-rider-bio-image]");
    const bioCredit = riderBioDialog.querySelector("[data-rider-bio-credit]");
    const bioName = riderBioDialog.querySelector("[data-rider-bio-name]");
    const bioMeta = riderBioDialog.querySelector("[data-rider-bio-meta]");
    const bioHighlight = riderBioDialog.querySelector("[data-rider-bio-highlight]");
    const bioText = riderBioDialog.querySelector("[data-rider-bio-text]");
    const bioEvents = riderBioDialog.querySelector("[data-rider-bio-events]");
    const bioSource = riderBioDialog.querySelector("[data-rider-bio-source]");

    riderCards.forEach((card) => {
      const originalLink = card.querySelector(".rider-card-link");
      if (!originalLink) return;
      const openButton = document.createElement("button");
      openButton.type = "button";
      openButton.className = originalLink.className;
      openButton.dataset.openRiderBio = "";
      openButton.innerHTML = 'Read rider bio <span aria-hidden="true">→</span>';
      originalLink.replaceWith(openButton);

      openButton.addEventListener("click", () => {
        const name = card.querySelector("h2").textContent.trim();
        const image = card.querySelector(".rider-database-photo img");
        const credit = card.querySelector(".rider-database-photo figcaption");
        const meta = card.querySelector(".rider-card-meta");
        const highlight = card.querySelector(".rider-highlight");
        const source = card.querySelector(".rider-database-copy a");
        const events = card.querySelector(".rider-event-tags");

        bioName.textContent = name;
        bioMeta.textContent = Array.from(meta.children).map((item) => item.textContent).join(" / ");
        bioHighlight.textContent = highlight?.textContent ?? "";
        bioText.textContent = riderBios[name] ?? card.querySelector(".rider-database-copy > p")?.textContent ?? "";
        bioImage.src = image.src;
        bioImage.alt = image.alt;
        bioCredit.replaceChildren(...Array.from(credit.childNodes, (node) => node.cloneNode(true)));

        bioEvents.replaceChildren();
        if (events) {
          Array.from(events.children).forEach((event) => bioEvents.append(event.cloneNode(true)));
          bioEvents.hidden = false;
        } else {
          bioEvents.hidden = true;
        }

        if (source) {
          bioSource.href = source.href;
          bioSource.firstChild.textContent = "Further reading: " + source.textContent.trim().replace("→", "").trim() + " ";
          bioSource.hidden = false;
        } else {
          bioSource.hidden = true;
        }
        riderBioDialog.showModal();
      });
    });

    riderBioDialog.querySelector("[data-close-rider-bio]")?.addEventListener("click", () => riderBioDialog.close());
    riderBioDialog.addEventListener("click", (event) => {
      if (event.target === riderBioDialog) riderBioDialog.close();
    });
  }
}
