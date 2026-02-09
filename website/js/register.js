document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registerForm");

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    fetch("register.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.text())
    .then(text => {
      text = text.trim();

      if (text === "success") {
        alert("Registration successful!");
        window.location.href = "login.php";
      } else if (text.startsWith("error:")) {
        alert("Registration failed: " + text.substring(6).trim());
      } else {
        alert("Unexpected server response: " + text);
      }
    })
    .catch(err => {
      alert("An error occurred: " + err.message);
    });
  });
});



  // // Simulate backend response (for testing only)
  // setTimeout(() => {
  //   alert("Registration was successful.");
  //   window.location.href = "login.php";
  //   }, 500);
