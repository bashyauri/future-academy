// Alpine.js is already included by Livewire Flux
// No need to import and start it manually

document.addEventListener("livewire:init", () => {
    Livewire.hook("commit", ({ succeed }) => {
        succeed(() => {
            // Re-render math globally after Livewire updates the DOM
            if (window.renderMathInElement) {
                window.renderMathInElement(document.body, {
                    delimiters: [
                        { left: "$", right: "$", display: false },
                        { left: "$$", right: "$$", display: true },
                        { left: "\\(", right: "\\)", display: false },
                        { left: "\\[", right: "\\]", display: true },
                    ],
                });
            }
        });
    });
});
