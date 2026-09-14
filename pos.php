<?php
session_start();

if(!isset($_SESSION["user"])){
    header("Location: login.php");
    exit();
}

require_once "dbase.php";

$sql = "SELECT * FROM products WHERE stock_quantity > 0";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StockIT — Point of Sale</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">

</head>

<body>

    <div class="app">

        <!-- SIDEBAR -->
        <aside class="sidebar">

            <div class="brand">
                <div class="brand-mark">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 7L12 3L20 7V17L12 21L4 17V7Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><path d="M4 7L12 11L20 7" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 11V21" stroke="white" stroke-width="1.6"/></svg>
                </div>
                <div class="brand-word">Stock<span>IT</span></div>
            </div>

            <div class="nav-section-label">MENU</div>

            <nav>
                <a class="nav-item" href="index.php">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="3" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="12" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="16" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
                    Dashboard
                </a>
                <a class="nav-item" href="pos.php">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="2.5" y="6" width="19" height="13" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 10.5H21.5" stroke="currentColor" stroke-width="1.6"/><path d="M7 14H11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Point of Sale
                </a>
                <a class="nav-item" href="products.php">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5L12 3L21 7.5V16.5L12 21L3 16.5V7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 7.5L12 12M12 12L21 7.5M12 12V21" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Products
                </a>
                <a class="nav-item" href="#">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 4V15C4 17.2091 5.79086 19 8 19H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 15L12 10L15 13L20 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Sales History
                </a>
                <a class="nav-item" href="#">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 3H14L19 8V19C19 20.1046 18.1046 21 17 21H6C4.89543 21 4 20.1046 4 19V5C4 3.89543 4.89543 3 6 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 12H15M8 16H15M8 8.5H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Sales Report
                </a>
            </nav>
        </aside>


        <!-- MAIN CONTENT -->
        <main class="main">

            <div class="topbar">
                <div>
                    <h1 class="page-title">Point of Sale</h1>
                    <p class="page-sub">
                        Create a new sales transaction.
                    </p>
                </div>
            </div>

            <div class="pos-layout">

            <!-- PRODUCT SELECTION -->
            <div class="pos-products">

                <div class="pos-card">

                    <div class="pos-card-header">
                        <h2>Products</h2>
                        <p>Select a product to add to the order.</p>
                    </div>

                    <div class="pos-search">

                        <input
                            type="text"
                            id="posProductSearch"
                            class="form-control"
                            placeholder="Search products...">

                    </div>

                    <div class="pos-product-list">
                        <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <button
                                type="button"
                                class="pos-product-item"
                                data-id="<?php echo $row['product_id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['product_name']); ?>"
                                data-price="<?php echo $row['price']; ?>"
                                data-stock="<?php echo $row['stock_quantity']; ?>"
                            >

                                <div>
                                    <strong>
                                        <?php echo htmlspecialchars($row['product_name']); ?>
                                    </strong>

                                    <span class="product-price">
                                        ₱<?php echo number_format($row['price'], 2); ?>
                                    </span>

                                    <span>
                                        <?php echo $row['stock_quantity']; ?> in stock
                                    </span>
                                </div>
                            </button>
                        <?php
                        }
                        ?>
                    </div>

                </div>

            </div>


            <!-- CURRENT ORDER -->
            <div class="pos-cart">

                <div class="pos-card">

                    <div class="pos-card-header">
                        <h2>Current Order</h2>
                        <p>Items added to this transaction.</p>
                    </div>

                    <div class="cart-order-search">
                        <input
                            type="text"
                            id="cartSearch"
                            class="form-control"
                            placeholder="Search current order...">
                    </div>

                    <div class="cart-header-row">
                        <span>PRODUCT</span>
                        <span>QTY</span>
                        <span>PRICE</span>
                        <span>TOTAL</span>
                    </div>

                    <div class="cart-items" id="cartItems">
                    </div>


                    <div class="cart-summary">

                        <div>
                            <span>Total</span>
                            <strong id="cartTotal">₱0.00</strong>
                        </div>

                        <div class="mb-3">

                            <label for="payment" class="form-label">
                                Payment
                            </label>

                            <input
                                type="number"
                                id="payment"
                                class="form-control"
                                placeholder="0.00"
                                step="0.01"
                                min="0">

                        </div>

                        <div>
                            <span>Change</span>
                            <strong id="changeAmount">₱0.00</strong>
                        </div>

                        <button type="button"
                                id="completeSaleBtn"
                                class="btn btn-primary w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#checkoutModal"
                                disabled>
                            Checkout
                        </button>

                    </div>

                </div>

            </div>

        </div>

        </main>

        <!-- CHECKOUT MODAL -->
        <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content checkout-modal">

                    <!-- MODAL HEADER -->
                    <div class="checkout-modal-header">

                        <div>
                            <h3>Checkout</h3>
                            <p>Review the transaction before completing the sale.</p>
                        </div>

                        <button
                            type="button"
                            class="checkout-close"
                            data-bs-dismiss="modal">
                            ×
                        </button>

                    </div>


                    <!-- MODAL BODY -->
                    <div class="checkout-modal-body">

                        <!-- ITEMS -->
                        <div class="checkout-section-title">
                            ITEMS
                        </div>

                        <div
                            class="checkout-items"
                            id="checkoutItems">
                        </div>


                        <!-- TOTAL -->
                        <div class="checkout-total-row">

                            <span>Total Due</span>

                            <strong id="checkoutTotal">
                                ₱0.00
                            </strong>

                        </div>


                        <!-- PAYMENT INFORMATION -->
                        <div class="checkout-payment-info">

                            <div>
                                <span>Amount Received</span>

                                <strong id="checkoutPayment">
                                    ₱0.00
                                </strong>
                            </div>

                            <div>
                                <span>Change</span>

                                <strong id="checkoutChange">
                                    ₱0.00
                                </strong>
                            </div>

                        </div>


                        <!-- PAYMENT METHOD -->
                        <div class="checkout-section-title">
                            PAYMENT METHOD
                        </div>

                        <div class="payment-methods">

                            <button
                                type="button"
                                class="payment-method active"
                                data-method="Cash">
                                Cash
                            </button>

                            <button
                                type="button"
                                class="payment-method"
                                data-method="GCash">
                                GCash
                            </button>

                        </div>


                        <!-- BUTTONS -->
                        <div class="checkout-modal-actions">

                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                                Cancel
                            </button>

                            <button
                                type="button"
                                id="confirmCheckoutBtn"
                                class="btn btn-primary">
                                Complete Sale
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    
    <script>
        //adding total product price
        document.addEventListener("DOMContentLoaded", function () {

            const searchInput = document.getElementById("posProductSearch");
            const productButtons = document.querySelectorAll(".pos-product-item");

            const cartItems = document.getElementById("cartItems");
            const cartTotal = document.getElementById("cartTotal");
            const cartSearch = document.getElementById("cartSearch");

            let cart = [];


            //search product

            searchInput.addEventListener("input", function () {

                const searchText = this.value.toLowerCase().trim();

                productButtons.forEach(function (button) {

                    const productName = button.dataset.name.toLowerCase();

                    if (productName.includes(searchText)) {
                        button.classList.remove("hidden");
                    } else {
                        button.classList.add("hidden");
                    }

                });

            });


            //order search

            function filterCart() {

                const searchText = cartSearch.value
                    .toLowerCase()
                    .trim();

                const cartRows = cartItems.querySelectorAll(".cart-item");

                cartRows.forEach(function (row) {

                    const productName = row
                        .querySelector(".cart-product-name")
                        .textContent
                        .toLowerCase()
                        .trim();

                    if (productName.includes(searchText)) {

                        row.style.setProperty("display", "grid", "important");

                    } else {

                        row.style.setProperty("display", "none", "important");

                    }

                });

            }


            // Run search whenever user types
            cartSearch.addEventListener("input", function () {
                filterCart();
            });


            // add product

            productButtons.forEach(function (button) {

                button.addEventListener("click", function () {

                    const productId = button.dataset.id;
                    const productName = button.dataset.name;
                    const productPrice = parseFloat(button.dataset.price);
                    const productStock = parseInt(button.dataset.stock);

                    // check if product already exists in cart
                    const existingProduct = cart.find(function (item) {
                        return item.id === productId;
                    });


                    // if product already exists
                    if (existingProduct) {

                        // increase quantity only if stock is available
                        if (existingProduct.quantity < existingProduct.stock) {
                            existingProduct.quantity++;
                        }

                    } else {

                        // add new product
                        cart.push({
                            id: productId,
                            name: productName,
                            price: productPrice,
                            stock: productStock,
                            quantity: 1
                        });

                    }

                    displayCart();

                });

            });


            //diplay in cart
            function displayCart() {

                cartItems.innerHTML = "";

                let total = 0;


                cart.forEach(function (item) {

                    const itemTotal = item.price * item.quantity;

                    total += itemTotal;


                    const cartItem = document.createElement("div");

                    cartItem.classList.add("cart-item");


                    cartItem.innerHTML = `
                        <div class="cart-product-name">
                            ${item.name}
                        </div>

                        <div class="cart-quantity">

                            <button
                                type="button"
                                class="quantity-btn decrease-btn">
                                −
                            </button>

                            <input
                                type="number"
                                class="quantity-input"
                                value="${item.quantity}"
                                min="1"
                                max="${item.stock}"
                            >

                            <button
                                type="button"
                                class="quantity-btn increase-btn">
                                +
                            </button>

                        </div>

                        <div class="cart-unit-price">
                            ₱${item.price.toFixed(2)}
                        </div>

                        <div class="cart-item-total">

                            <strong>
                                ₱${itemTotal.toFixed(2)}
                            </strong>

                            <button
                                type="button"
                                class="remove-item-btn">
                                Remove
                            </button>

                        </div>
                    `;


                    cartItems.appendChild(cartItem);


                    //bawas

                    cartItem
                        .querySelector(".decrease-btn")
                        .addEventListener("click", function () {

                            if (item.quantity > 1) {

                                item.quantity--;

                                displayCart();

                            }

                        });


                    //dagdag

                    cartItem
                        .querySelector(".increase-btn")
                        .addEventListener("click", function () {

                            if (item.quantity < item.stock) {

                                item.quantity++;

                                displayCart();

                            }

                        });


                    //typing quantity

                    cartItem
                        .querySelector(".quantity-input")
                        .addEventListener("change", function () {

                            let newQuantity = parseInt(this.value);


                            // Invalid number
                            if (isNaN(newQuantity) || newQuantity < 1) {
                                newQuantity = 1;
                            }


                            // cannot exceed available stock
                            if (newQuantity > item.stock) {
                                newQuantity = item.stock;
                            }


                            item.quantity = newQuantity;

                            displayCart();

                        });


                    //remove product
                    cartItem
                        .querySelector(".remove-item-btn")
                        .addEventListener("click", function () {

                            cart = cart.filter(function (cartItem) {

                                return cartItem.id !== item.id;

                            });

                            displayCart();

                        });

                });


                //update the total

                cartTotal.textContent = "₱" + total.toFixed(2);


                //current order searching

                filterCart();

                // ==========================================
                // CHECKOUT MODAL
                // ==========================================

                const checkoutItems =
                    document.getElementById("checkoutItems");

                const checkoutTotal =
                    document.getElementById("checkoutTotal");

                const checkoutPayment =
                    document.getElementById("checkoutPayment");

                const checkoutChange =
                    document.getElementById("checkoutChange");

                const completeSaleBtn =
                    document.getElementById("completeSaleBtn");


                // Open Checkout Modal
                completeSaleBtn.addEventListener("click", function () {

                    // Clear previous items
                    checkoutItems.innerHTML = "";

                    // Display cart products
                    cart.forEach(function (item) {

                        const itemTotal =
                            item.price * item.quantity;

                        const checkoutItem =
                            document.createElement("div");

                        checkoutItem.classList.add("checkout-item");

                        checkoutItem.innerHTML = `
                            <div>
                                <div class="checkout-item-name">
                                    ${item.name}
                                </div>

                                <div class="checkout-item-qty">
                                    × ${item.quantity}
                                </div>
                            </div>

                            <div class="checkout-item-price">
                                ₱${itemTotal.toFixed(2)}
                            </div>
                        `;

                        checkoutItems.appendChild(checkoutItem);

                    });


                    // Get total
                    const total =
                        parseFloat(
                            cartTotal.textContent.replace("₱", "")
                        ) || 0;


                    // Get payment
                    const paymentInput =
                        document.getElementById("payment");

                    const payment =
                        parseFloat(paymentInput.value) || 0;


                    // Calculate change
                    const change =
                        payment - total;


                    // Display values
                    checkoutTotal.textContent =
                        "₱" + total.toFixed(2);

                    checkoutPayment.textContent =
                        "₱" + payment.toFixed(2);

                    checkoutChange.textContent =
                        "₱" + Math.max(change, 0).toFixed(2);

                    // ==========================================
                    // COMPLETE SALE
                    // ==========================================

                    const confirmCheckoutBtn =
                        document.getElementById("confirmCheckoutBtn");

                    confirmCheckoutBtn.addEventListener("click", function () {

                        // Make sure cart is not empty
                        if (cart.length === 0) {

                            alert("Cart is empty.");

                            return;
                        }


                        // Create form data
                        const formData = new FormData();

                        formData.append(
                            "cart",
                            JSON.stringify(cart)
                        );

                        formData.append(
                            "payment",
                            paymentInput.value
                        );


                        // Disable button while processing
                        confirmCheckoutBtn.disabled = true;

                        confirmCheckoutBtn.textContent =
                            "Processing...";


                        // Send cart to checkout.php
                        fetch("checkout.php", {
                            method: "POST",
                            body: formData
                        })

                        .then(function (response) {

                            return response.json();

                        })

                        .then(function (data) {

                            console.log(data);


                            // ==========================================
                            // SUCCESS
                            // ==========================================

                            if (data.success) {

                                alert(
                                    "Sale completed successfully!\n\n" +
                                    "Transaction No.: " + data.sale_id
                                );


                            } else {

                                alert(
                                    "Checkout failed:\n" +
                                    data.message
                                );

                            }

                        })

                        .catch(function (error) {

                            console.error("Checkout error:", error);

                            alert(
                                "Something went wrong while processing the sale."
                            );

                        })

                        .finally(function () {

                            confirmCheckoutBtn.disabled = false;

                            confirmCheckoutBtn.textContent =
                                "Complete Sale";

                        });

                    });    
                });

            }

        });
        </script>

    
    <script>
        //sukli update
        document.addEventListener("DOMContentLoaded", function () {
            const paymentInput = document.getElementById("payment");
            const cartTotal = document.getElementById("cartTotal");
            const changeAmount = document.getElementById("changeAmount");
            const completeSaleBtn = document.getElementById("completeSaleBtn");


            paymentInput.addEventListener("input", function () {
                const total = parseFloat(cartTotal.textContent.replace("₱", "")) || 0;
                const payment = parseFloat(paymentInput.value) || 0;
                const change = payment - total;

                if (payment >= total && total > 0) {
                    changeAmount.textContent = "₱" + change.toFixed(2);
                    completeSaleBtn.disabled = false;
                } else {
                    changeAmount.textContent = "₱0.00";
                    completeSaleBtn.disabled = true;
                }


            });

        });
    </script>
    

</body>
</html>