(function () {
    "use strict";

    const STORAGE_KEY = "dizany-sounds-muted";

    function isMuted() {
        try {
            return localStorage.getItem(STORAGE_KEY) === "1";
        } catch (_) {
            return false;
        }
    }

    function setMuted(muted) {
        const next = Boolean(muted);
        try {
            localStorage.setItem(STORAGE_KEY, next ? "1" : "0");
        } catch (_) {}

        document.dispatchEvent(new CustomEvent("dizany:sound-changed", {
            detail: { muted: next }
        }));
        return next;
    }

    async function play(audio) {
        if (!audio || isMuted()) return false;
        try {
            await audio.play();
            return true;
        } catch (_) {
            return false;
        }
    }

    window.DizanySound = { isMuted, setMuted, play };
})();
