document.addEventListener("DOMContentLoaded", function () {
    const darkModeEnabled = localStorage.getItem("dark-mode") === "true";
    const body = document.body;
    const icon = document.getElementById('darkModeIcon');

    if (darkModeEnabled) {
        body.classList.add("dark-mode");
        if (icon) {
            icon.classList.replace("fa-moon", "fa-sun");
        }
    }
});

function toggleDarkMode() {
    const body = document.body;
    const icon = document.getElementById('darkModeIcon');

    body.classList.toggle("dark-mode");

    if (body.classList.contains("dark-mode")) {
        localStorage.setItem("dark-mode", "true");
        if (icon) icon.classList.replace("fa-moon", "fa-sun");
    } else {
        localStorage.setItem("dark-mode", "false");
        if (icon) icon.classList.replace("fa-sun", "fa-moon");
    }
}