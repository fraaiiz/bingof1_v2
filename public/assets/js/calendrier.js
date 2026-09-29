const carousel = document.querySelector('[data-carousel]');

if (carousel) {
    const cards = Array.from(carousel.querySelectorAll('[data-course-card]'));
    const previousButton = carousel.querySelector('[data-previous]');
    const nextButton = carousel.querySelector('[data-next]');
    const position = carousel.querySelector('[data-position]');
    const selectedIndex = cards.findIndex((card) => !card.hidden);
    let activeIndex = selectedIndex >= 0 ? selectedIndex : 0;

    function showCourse(index) {
        activeIndex = index;

        cards.forEach((card, cardIndex) => {
            const isActive = cardIndex === activeIndex;
            card.hidden = !isActive;
            card.classList.toggle('active', isActive);
            card.setAttribute('aria-hidden', String(!isActive));
        });

        previousButton.disabled = activeIndex === 0;
        nextButton.disabled = activeIndex === cards.length - 1;
        if (position) {
            position.textContent = `Course ${activeIndex + 1} / ${cards.length}`;
        }
    }

    previousButton.addEventListener('click', () => showCourse(activeIndex - 1));
    nextButton.addEventListener('click', () => showCourse(activeIndex + 1));
    showCourse(activeIndex);
}