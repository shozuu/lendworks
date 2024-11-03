function setThemeOnLoad() {
    const body = document.querySelector('body');
    if (localStorage.theme === 'dark' || 
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        body.setAttribute('data-theme', 'dark');
    } else {
        body.setAttribute('data-theme', 'light');
    }
}


function switchTheme() { // on button click
    const body = document.querySelector('body');
    if (body.getAttribute('data-theme') === 'dark') {
        body.setAttribute('data-theme', 'light');
        localStorage.theme = 'light';
    } else {
        body.setAttribute('data-theme', 'dark');
        localStorage.theme = 'dark';
    }
}

export { setThemeOnLoad, switchTheme }