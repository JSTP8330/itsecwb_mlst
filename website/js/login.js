document.getElementById("login-form").addEventListener("submit", function (e) {
  e.preventDefault();

  const username = document.querySelector("input[name='username']").value.trim();
  const password = document.querySelector("input[name='password']").value;

  if (!username || !password) {
    alert("Please fill in both fields.");
    return;
  }

  const formData = new FormData(this);

  fetch("login.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(text => {
    text = text.trim();
    if (text === "success") {
      alert("Login successful!");
      window.location.href = "index.php";
    } else {
      alert("Login failed: " + text);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("An error occurred during login.");
  });

  // Simulate backend response (replace with fetch later)
  // setTimeout(() => {
  //   alert("Login successful (mock)");
  //   window.location.href = "index.php"; // Redirect to homepage/dashboard        -- CTRL + SHIFT + R do refresh ! -- 
  // }, 1000);


});
