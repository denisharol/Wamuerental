document.addEventListener("DOMContentLoaded", function () {
    let today = new Date().toISOString().split('T')[0];
    let moveInDate = document.getElementById("moveInDate");
    moveInDate.setAttribute("min", today);
    moveInDate.setAttribute("max", today);
    moveInDate.value = today;

    document.getElementById("tenantForm").addEventListener("submit", function (event) {
        event.preventDefault();

        let name = document.getElementById("name").value.trim();
        let idNumber = document.getElementById("idNumber").value.trim();
        let phone = document.getElementById("phone").value.trim();
        let email = document.getElementById("email").value.trim();
        let unit = document.getElementById("unit").value;
        let rentAmount = document.getElementById("rentAmount").value.trim();
        let securityDeposit = document.getElementById("securityDeposit").value.trim();


        if (!name || !idNumber || !phone || !email || !unit || !rentAmount || !securityDeposit) {
            alert("Please fill in all fields.");
            return;
        }

        if (!email.includes("@")) {
            alert("Please enter a valid email address.");
            return;
        }

        if (phone.length !== 9 || isNaN(phone)) {
            alert("Phone number must be exactly 9 digits.");
            return;
        }


        let formData = new FormData();
        formData.append("name", name);
        formData.append("idNumber", idNumber);
        formData.append("phone", "+2540" + phone);
        formData.append("email", email);
        formData.append("unit", unit);
        formData.append("moveInDate", moveInDate.value);
        formData.append("rentAmount", rentAmount);
        formData.append("securityDeposit", securityDeposit);

        fetch("create_account.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            if (data.includes("success")) {
                document.getElementById("tenantForm").reset();
                moveInDate.value = today;
            }
        })
        .catch(error => console.error("Error:", error));
    });
});
