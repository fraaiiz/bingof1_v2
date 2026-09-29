const countdownElement = document.getElementById('countdown');
const data = window.countdownData || {};
const startDate = data.start ? new Date(data.start).getTime() : 0;
const status = data.status || '';

function updateCountdown() {
    if (!countdownElement) return;

    const now = new Date().getTime();

    if (status === 'en_cours') {
        countdownElement.textContent = 'en cours';
        return;
    }

    if (status === 'a_venir' && startDate) {
        const diff = startDate - now;

        if (diff <= 0) {
            countdownElement.textContent = 'départ imminent';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        countdownElement.textContent = 'dans ' + (days > 0 ? days + 'j ' : '') + hours + 'h ' + minutes + 'm ' + seconds + 's';
        return;
    }

    countdownElement.textContent = 'Aucune course à venir';
}

updateCountdown();
setInterval(updateCountdown, 1000);