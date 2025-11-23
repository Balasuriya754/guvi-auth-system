$(document).ready(function () {

    $("#loginForm").on("submit", function (e) {
        e.preventDefault();  // Prevent default form submission

        let email = $("#email").val().trim();
        let password = $("#password").val().trim();

        if (email === "" || password === "") {
            alert("Please enter both email and password");
            return;
        }

        $.ajax({
            url: "php/login.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                email: email,
                password: password
            }),
            dataType: "json",

            success: function (response) {
                if (response.status === "success") {

                    // Store token + user_id in LOCALSTORAGE ONLY (Rule!)
                    localStorage.setItem("guvi_token", response.token);
                    localStorage.setItem("guvi_user_id", response.user_id);

                    alert("Login successful!");
                    window.location.href = "profile.html";

                } else {
                    alert(response.message || "Invalid Credentials");
                }
            },

            error: function (xhr) {
                console.log(xhr.responseText);
                alert("Error: Unable to login!");
            }
        });

    });

});
