const refreshButton = document.querySelector("#dashboard-refresh");
const dashboardStatus = document.querySelector("#dashboard-status");
const categoryChart = document.querySelector("#category-chart");
const lowStockBody = document.querySelector("#low-stock-body");

let loading = false;

function renderCategories(categories) {
    categoryChart.replaceChildren();

    if (categories.length === 0) {
        const item = document.createElement("li");
        item.textContent = "No animals are recorded.";
        categoryChart.append(item);
        return;
    }

    const largestCount = Math.max(
        1,
        ...categories.map((category) => category.animal_count)
    );

    for (const category of categories) {
        const item = document.createElement("li");
        const heading = document.createElement("div");
        const label = document.createElement("span");
        const count = document.createElement("span");
        const track = document.createElement("div");
        const bar = document.createElement("div");

        heading.className = "chart-label";
        label.textContent = category.category;
        count.textContent = category.animal_count.toLocaleString("en-AU");

        track.className = "chart-track";
        track.setAttribute("aria-hidden", "true");

        bar.className = "chart-bar";
        bar.style.width =
            `${(category.animal_count / largestCount) * 100}%`;

        heading.append(label, count);
        track.append(bar);
        item.append(heading, track);
        categoryChart.append(item);
    }
}

function renderLowStock(products) {
    lowStockBody.replaceChildren();

    if (products.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 3;
        cell.textContent = "No merchandise is recorded.";

        row.append(cell);
        lowStockBody.append(row);
        return;
    }

    for (const product of products) {
        const row = document.createElement("tr");

        const values = [
            product.ItemID,
            product.Description || "Unnamed product",
            product.QuantityOnHand
        ];

        for (const value of values) {
            const cell = document.createElement("td");
            cell.textContent = String(value);
            row.append(cell);
        }

        if (product.QuantityOnHand <= 0) {
            row.classList.add("stock-empty");
            row.lastElementChild.textContent =
                `${product.QuantityOnHand} — out of stock`;
        }

        lowStockBody.append(row);
    }
}

async function loadDashboard() {
    if (loading) return;

    loading = true;
    refreshButton.disabled = true;
    dashboardStatus.textContent = "Updating dashboard...";

    try {
        const response = await fetch("dashboard_api.php", {
            cache: "no-store",
            headers: { Accept: "application/json" }
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Dashboard request failed.");
        }

        document.querySelector("#total-animals").textContent =
            data.totals.animals.toLocaleString("en-AU");

        document.querySelector("#total-customers").textContent =
            data.totals.customers.toLocaleString("en-AU");

        document.querySelector("#total-sales").textContent =
            data.totals.sales.toLocaleString("en-AU");

        renderCategories(data.categories);
        renderLowStock(data.low_stock);

        const updatedTime = new Date(data.updated_at).toLocaleTimeString(
            "en-AU",
            { hour: "2-digit", minute: "2-digit", second: "2-digit" }
        );

        dashboardStatus.textContent = `Last updated at ${updatedTime}.`;

    } catch (error) {
        dashboardStatus.textContent =
            "Update failed. Any previous figures remain displayed. " +
            "Check the connection and try Refresh data.";

    } finally {
        loading = false;
        refreshButton.disabled = false;
    }
}

refreshButton.addEventListener("click", loadDashboard);

setInterval(() => {
    if (!document.hidden) {
        loadDashboard();
    }
}, 60000);

loadDashboard();