document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registerForm");
  const fileInput = document.getElementById('profile_picture');
  const previewImage = document.getElementById('preview-image');
  const placeholder = document.querySelector('.placeholder');

  if (fileInput) {
    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      
      if (file) {
        const fileExtension = file.name.split('.').pop().toLowerCase();
        const allowedExtensions = ['bmp', 'jpeg', 'jpg', 'png'];
        
        if (!allowedExtensions.includes(fileExtension)) {
          alert('Invalid file type. Only BMP, JPEG, JPG, and PNG are allowed.');
          fileInput.value = '';
          return;
        }
        
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
          alert('File size too large. Maximum size is 5MB.');
          fileInput.value = '';
          return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
          previewImage.src = e.target.result;
          previewImage.classList.add('show');
          if (placeholder) {
            placeholder.style.display = 'none';
          }
        };
        
        reader.readAsDataURL(file);
      } else {
        previewImage.src = '';
        previewImage.classList.remove('show');
        if (placeholder) {
          placeholder.style.display = 'block';
        }
      }
    });
  }

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