
    function togglePassword(el) {
    const input = el.previousElementSibling;
    if (input.type === "password") {
    input.type = "text";
    el.textContent = "Sakrij šifru";
} else {
    input.type = "password";
    el.textContent = "Prikaži šifru";
}
}

