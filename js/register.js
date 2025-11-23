$(document).ready(function () {

    $("#registerForm").on("submit", function (e) {
        e.preventDefault();  // prevent normal form submission

        let full_name = $("#fullname").val().trim();
        let email = $("#email").val().trim();
        let password = $("#password").val().trim();

        if (full_name === "" || email === "" || password === "") {
            alert("Please fill all fields");
            return;
        }

        $.ajax({
            url: "php/register.php",
            method: "POST",
            data: JSON.stringify({
                fullname: full_name,
                email: email,
                password: password
            }),
            contentType: "application/json",
            dataType: "json",

            success: function (response) {
                if (response.status === "success") {
                    alert("Registration successful! Redirecting to login...");
                    window.location.href = "login.html";
                } else {
                    alert(response.message || "Registration failed!");
                }
            },

            error: function (xhr) {
                console.log(xhr.responseText);
                alert("Error: Unable to register!");
            }
        });
    });

});
