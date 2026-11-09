const surnameInput = document.querySelector("#customer-surname");
const statusMessage = document.querySelector("#customer-status");
const suggestions = document.querySelector("#customer-suggestions");
const detailsPanel = document.querySelector("#customer-details-panel");
const details = document.querySelector("#customer-details");

let timer;
let requestVersion = 0;

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: { Accept: "application/json" }
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || "The request failed.");
    }

    return data;
}

function clearCustomerDisplay() {
    suggestions.replaceChildren();
    details.replaceChildren();
    detailsPanel.hidden = true;
}

function displayCustomer(customer) {
    const fields = [
        ["Customer ID", customer.CustomerID],
        ["First name", customer.FirstName],
        ["Family name", customer.LastName],
        ["Phone", customer.Phone],
        ["Address", customer.Address],
        ["Postcode / ZIP", customer.ZipCode],
        ["City ID", customer.CityID]
    ];

    details.replaceChildren();

    for (const [label, value] of fields) {
        const term = document.createElement("dt");
        const description = document.createElement("dd");

        term.textContent = label;
        description.textContent =
            value === null || value === ""
                ? "Not recorded"
                : String(value);

        details.append(term, description);
    }

    detailsPanel.hidden = false;
}

async function selectCustomer(id) {
    clearTimeout(timer);

    const version = ++requestVersion;

    suggestions.replaceChildren();
    detailsPanel.hidden = true;
    statusMessage.textContent = "Loading customer details...";

    try {
        const data = await fetchJson(
            `customer_api.php?id=${encodeURIComponent(id)}`
        );

        if (version !== requestVersion) return;

        displayCustomer(data.customer);

        statusMessage.textContent =
            `Customer ${data.customer.CustomerID} selected. ` +
            "Details are displayed below.";

        surnameInput.focus();

    } catch (error) {
        if (version !== requestVersion) return;

        statusMessage.textContent =
            "Customer details could not be loaded. Please try again.";
    }
}

async function searchCustomers(query, version) {
    try {
        const data = await fetchJson(
            `customer_api.php?q=${encodeURIComponent(query)}`
        );

        if (version !== requestVersion) return;

        suggestions.replaceChildren();

        if (data.customers.length === 0) {
            statusMessage.textContent = "No matching customers found.";
            return;
        }

        for (const customer of data.customers) {
            const item = document.createElement("li");
            const button = document.createElement("button");

            button.type = "button";
            button.className = "customer-suggestion";

            const fullName = [
                customer.FirstName,
                customer.LastName
            ].filter(Boolean).join(" ");

            button.textContent =
                `${fullName} — Customer ID ${customer.CustomerID}`;

            button.addEventListener("click", () => {
                selectCustomer(customer.CustomerID);
            });

            item.append(button);
            suggestions.append(item);
        }

        statusMessage.textContent =
            `${data.customers.length} suggestions shown. ` +
            "Up to 10 are displayed; type more letters to narrow the search.";

    } catch (error) {
        if (version !== requestVersion) return;

        statusMessage.textContent =
            "Customer search is unavailable. Please try again.";
    }
}

surnameInput.addEventListener("input", () => {
    clearTimeout(timer);

    const version = ++requestVersion;
    const query = surnameInput.value.trim();

    clearCustomerDisplay();

    if (query === "") {
        statusMessage.textContent = "Enter a family name to begin.";
        return;
    }

    statusMessage.textContent = "Searching...";

    timer = setTimeout(() => {
        searchCustomers(query, version);
    }, 250);
});