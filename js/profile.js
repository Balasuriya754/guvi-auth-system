$(document).ready(function () {

    // -------------------------------------------
    // CHECK FOR LOGIN TOKEN (GUVI RULE)
    // -------------------------------------------
    let token = localStorage.getItem("guvi_token");
    let userId = localStorage.getItem("guvi_user_id");

    if (!token || !userId) {
        alert("You must login first");
        window.location.href = "login.html";
        return;
    }

    // -------------------------------------------
    // FETCH PROFILE DETAILS
    // -------------------------------------------
    function loadProfile() {
        $.ajax({
            url: "php/profile.php?action=get",
            method: "POST",
            contentType: "application/json",
            headers: {
                "Authorization": "Bearer " + token
            },
            data: JSON.stringify({ user_id: userId }),
            success: function (res) {
                if (res.status === "success") {

                    $("#fullname").val(res.data.fullname);
                    $("#age").val(res.data.age);
                    $("#dob").val(res.data.dob);
                    $("#contact").val(res.data.contact);
                    $("#address").val(res.data.address);

                } else {
                    alert(res.message);
                }
            },
            error: function () {
                alert("Error fetching profile!");
            }
        });
    }

    loadProfile();


    // -------------------------------------------
    // UPDATE PROFILE
    // -------------------------------------------
    $("#profileForm").on("submit", function (e) {
        e.preventDefault();

        let data = {
            user_id: userId,
            age: $("#age").val(),
            dob: $("#dob").val(),
            contact: $("#contact").val(),
            address: $("#address").val()
        };

        $.ajax({
            url: "php/profile.php?action=update",
            method: "POST",
            contentType: "application/json",
            headers: {
                "Authorization": "Bearer " + token
            },
            data: JSON.stringify(data),
            success: function (res) {
                if (res.status === "success") {
                    alert("Profile updated successfully!");
                } else {
                    alert(res.message);
                }
            },
            error: function () {
                alert("Error updating profile!");
            }
        });

    });


    // -------------------------------------------
    // LOGOUT
    // -------------------------------------------
    $("#logoutBtn").on("click", function () {
        localStorage.removeItem("guvi_token");
        localStorage.removeItem("guvi_user_id");
        window.location.href = "login.html";
    });

});
