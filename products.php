<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}
//database conn
require_once "dbase.php";

if (!isset($_SESSION["store_id"])) {
    die("Store account is not configured.");
}

$storeId = $_SESSION["store_id"];

$currentPage = basename($_SERVER['PHP_SELF']);


//Success notification
$successMessage = "";


if (isset($_SESSION["success"])) {
    $successMessage = $_SESSION["success"];
    unset($_SESSION["success"]);
}

//error notification
$errorMessage = "";

if (isset($_SESSION["error"])) {
    $errorMessage = $_SESSION["error"];
    unset($_SESSION["error"]);

}

//add data
if (isset($_POST["add_product"])) {

    $productName = $_POST["product_name"];
    $category = $_POST["category"];
    $price = $_POST["price"];
    $cost = $_POST["cost"];
    $stockQuantity = $_POST["stock_quantity"];
    $reorderLevel = $_POST["reorder_level"];


    //check duplicate product
    $checkSql = "SELECT product_id FROM products WHERE product_name = ? AND category = ? AND store_id = ?";

    $checkStmt = mysqli_prepare($conn, $checkSql);

    mysqli_stmt_bind_param($checkStmt, "ssi", $productName, $category, $storeId);

    mysqli_stmt_execute($checkStmt);

    $checkResult = (mysqli_stmt_get_result($checkStmt));


    //checck if duplicate product exist
    if (mysqli_num_rows($checkResult) > 0 ) {
        $_SESSION["error"] = "Product already exist";
        header("Location: products.php");
        exit();

    }



    $sql = "INSERT INTO products (product_name, category, price, cost, stock_quantity, reorder_level, store_id) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ssddiii", $productName, $category, $price, $cost, $stockQuantity, $reorderLevel, $storeId);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION["success"] = "Product added successfully!";
        header("Location: products.php");
        exit();
    }
}

//update data
if (isset($_POST["update_product"])) {
    $productId = $_POST["product_id"];
    $productName = $_POST["product_name"];
    $category = $_POST["category"];
    $price = $_POST["price"];
    $cost = $_POST["edit_cost"];
    $stockQuantity = $_POST["stock_quantity"];
    $reorderLevel = $_POST["reorder_level"];


    //check duplicate item in database
    $checkSql = "SELECT product_id FROM products WHERE product_name = ? AND category = ? AND product_id != ? AND store_id = ?";

    $checkStmt = mysqli_prepare($conn, $checkSql);

    mysqli_stmt_bind_param($checkStmt, "ssii", $productName, $category, $productId, $storeId);

    mysqli_stmt_execute($checkStmt);

    $checkResult = mysqli_stmt_get_result($checkStmt);


    //validate if it exist
    if(mysqli_num_rows($checkResult) > 0 ){
        $_SESSION["error"] = "Another product with the same name and category already exists";
        header("Location: products.php");
        exit();
    }

    $sql = "UPDATE products 
            SET product_name = ?, category = ?, price = ?, cost = ?, stock_quantity = ?, reorder_level = ?
            WHERE product_id = ?
            AND store_id = ?";

            $stmt = mysqli_prepare($conn,$sql);

            mysqli_stmt_bind_param($stmt, "ssddiiii", $productName, $category, $price, $cost, $stockQuantity, $reorderLevel, $productId, $storeId);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["success"] = "Product update successfully";
                header("Location: products.php");
                exit();
                        
            }
}

// delete data
if (isset($_POST["delete_product"])) {

    $productId = $_POST["product_id"];

    $sql = "
        DELETE FROM products
        WHERE product_id = ?
        AND store_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $productId,
        $storeId
    );

    if (mysqli_stmt_execute($stmt)) {

        $_SESSION["success"] = "Product deleted successfully";
        header("Location: products.php");
        exit();

    }
}

//fetch data

$sql = "SELECT * FROM products WHERE store_id = ?";
$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $storeId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$productsRow = mysqli_num_rows($result);
$productLabel = ($productsRow == 1) ? "Product" : "Products";


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StockIT — Products</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
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
      <a class="nav-item <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
        <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="3" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="12" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="16" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
        Dashboard
      </a>
      <a class="nav-item <?php echo $currentPage == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
        <svg viewBox="0 0 24 24" fill="none"><rect x="2.5" y="6" width="19" height="13" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 10.5H21.5" stroke="currentColor" stroke-width="1.6"/><path d="M7 14H11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Point of Sale
      </a>
      <a class="nav-item <?php echo $currentPage == 'products.php' ? 'active' : ''; ?>" href="products.php">
        <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5L12 3L21 7.5V16.5L12 21L3 16.5V7.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 7.5L12 12M12 12L21 7.5M12 12V21" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Products
      </a>
      <a class="nav-item <?php echo $currentPage == 'sales_history.php' ? 'active' : ''; ?>" href="sales_history.php">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 4V15C4 17.2091 5.79086 19 8 19H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 15L12 10L15 13L20 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sales History
      </a>
      <a class="nav-item <?php echo $currentPage == 'salesReport.php' ? 'active' : ''; ?>" href="salesReport.php">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 3H14L19 8V19C19 20.1046 18.1046 21 17 21H6C4.89543 21 4 20.1046 4 19V5C4 3.89543 4.89543 3 6 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 12H15M8 16H15M8 8.5H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Sales Report
      </a>
    </nav>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main">
        <?php if ($successMessage != "") { ?>

        <div class="toast-container position-fixed top-0 end-0 p-3">

            <div id="successToast"
                class="toast text-bg-success"
                role="alert"
                aria-live="assertive"
                aria-atomic="true">

                <div class="toast-body">
                    <?php echo $successMessage; ?>
                </div>

            </div>

        </div>

        <?php } ?>

        <?php if ($errorMessage != "") { ?>

        <div class="toast-container position-fixed top-0 end-0 p-3">

            <div id="errorToast"
                class="toast text-bg-danger"
                role="alert"
                aria-live="assertive"
                aria-atomic="true">

                <div class="toast-body">
                    <?php echo $errorMessage; ?>
                </div>

            </div>

        </div>

        <?php } ?>

        <div class="topbar">
            <div>
                <h1 class="page-title">Products</h1>
                <p class="page-sub">Manage your store products and inventory.</p>
            </div>
        </div>

        
        <div class="product-toolbar">

            <div class="product-search">
                <input
                    type="text"
                    id="searchProduct"
                    class="form-control"
                    placeholder="Search products...">
            </div>

            <div class="product-actions">

                <select 
                    id="statusFilter"
                    class="form-select product-status-filter">
                    <option value="all" selected>All Status</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>


                <button
                    type="button"
                    class="btn btn-primary add-product-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#addProductModal">
                    + Add Product
                </button>
            </div>

        </div>
    <div class="product-table-card">

        <!-- DELETE PRODUCT MODAL -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">
                    Delete Product
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div class="modal-body">

                <p>
                    Are you sure you want to delete
                    <strong id="deleteProductName"></strong>?
                </p>

                <p class="text-muted mb-0">
                    This action cannot be undone.
                </p>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cancel
                </button>

                <form action="products.php" method="POST">

                    <input
                        type="hidden"
                        name="product_id"
                        id="deleteProductId">

                    <button
                        type="submit"
                        name="delete_product"
                        class="btn btn-danger">
                        Delete Product
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

        <!-- EDIT PRODUCT MODAL -->
<div class="modal fade" id="editProductModal" tabindex="-1"
     aria-labelledby="editProductModalLabel" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">
                    Edit Product
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>


            <div class="modal-body">
                <form action="products.php" method="POST">

                    <input type="hidden" name="product_id" id="editProductId">

                    <div class="mb-3">
                        <label for="editProductName" class="form-label">
                            Product Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="editProductName"
                            name="product_name"
                            required>
                    </div>


                    <div class="mb-3">
                        <label for="editCategory" class="form-label">
                            Category
                        </label>

                        <select
                            class="form-select"
                            id="editCategory"
                            name="category"
                            required>

                            <option value="" disabled>
                                Select Category
                            </option>

                            <option value="Beverages">Beverages</option>
                            <option value="Snacks">Snacks</option>
                            <option value="Instant Noodles">Instant Noodles</option>
                            <option value="Canned Goods">Canned Goods</option>
                            <option value="Rice & Grains">Rice & Grains</option>
                            <option value="Dairy">Dairy</option>
                            <option value="Bread & Bakery">Bread & Bakery</option>
                            <option value="Condiments & Sauces">Condiments & Sauces</option>
                            <option value="Frozen Foods">Frozen Foods</option>
                            <option value="Personal Care">Personal Care</option>
                            <option value="Household">Household</option>
                            <option value="Other">Other</option>

                        </select>
                    </div>


                    <div class="mb-3">
                        <label for="editPrice" class="form-label">
                            Price
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="editPrice"
                            name="price"
                            step="0.01"
                            min="0"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cost</label>
                        <input
                            type="number"
                            name="edit_cost"
                            id="edit_cost"
                            class="form-control"
                            step="0.01"
                            min="0"
                            required>
                    </div>


                    <div class="mb-3">
                        <label for="editStock" class="form-label">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="editStock"
                            name="stock_quantity"
                            min="0"
                            required>
                    </div>


                    <div class="mb-3">
                        <label for="editReorder" class="form-label">
                            Reorder Level
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="editReorder"
                            name="reorder_level"
                            min="0"
                            required>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="submit"
                            name="update_product"
                            class="btn btn-primary">
                            Update Product
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

    </div>
        <!-- ADD PRODUCT MODAL -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">
            

            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">
                    Add Product
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>


            <div class="modal-body">
                <?php
                
                ?>

                <form action="products.php" method="POST">
                    <div class="mb-3">

                        <label for="productName" class="form-label">
                            Product Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="productName"
                            name="product_name"
                            placeholder="Enter product name"
                            required>
                    </div>


                    <div class="mb-3">
                        <label for="category" class="form-label">
                            Category
                        </label>

                        <select class="form-select" id="category" name="category" required>
                            <option value="" selected disabled>
                                Select Category
                            </option>
                            <option value="Beverages">Beverages</option>
                            <option value="Snacks">Snacks</option>
                            <option value="Instant Noodles">Instant Noodles</option>
                            <option value="Canned Goods">Canned Goods</option>
                            <option value="Rice & Grains">Rice & Grains</option>
                            <option value="Dairy">Dairy</option>
                            <option value="Bread & Bakery">Bread & Bakery</option>
                            <option value="Condiments & Sauces">Condiments & Sauces</option>
                            <option value="Frozen Foods">Frozen Foods</option>
                            <option value="Personal Care">Personal Care</option>
                            <option value="Household">Household</option>
                            <option value="Other">Other</option>

                        </select>
                    </div>


                    <div class="mb-3">
                        <label for="price" class="form-label">
                            Price
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="price"
                            name="price"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cost</label>
                        <input
                            type="number"
                            name="cost"
                            class="form-control"
                            step="0.01"
                            min="0"
                            required>
                    </div>


                    <div class="mb-3">
                        <label for="stockQuantity" class="form-label">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="stockQuantity"
                            name="stock_quantity"
                            placeholder="Enter stock quantity"
                            min="0"
                            required>
                    </div>


                    <div class="mb-3">
                        <label for="reorderLevel" class="form-label">
                            Reorder Level
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="reorderLevel"
                            name="reorder_level"
                            placeholder="Enter reorder level"
                            min="0"
                            required>
                    </div>

                    
                

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cancel
                </button>

                <button
                        type="submit"
                        name = "add_product"
                        class="btn btn-primary">
                        Add Product
                </button>


                

            </div>
            </form>

        </div>

    </div>

</div>

    <div class="table-header">
        <h2>Product List</h2>
        <span><?php echo $productsRow . "" . $productLabel;?></span>
    </div>

    <div class="table-responsive product-table-scroll">

        <table class="table product-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php
                    while($row = mysqli_fetch_assoc($result)) {
                ?>

                <?php

                    if ($row["stock_quantity"] == 0) {
                        $status = "out_of_stock";
                    } elseif ($row["stock_quantity"] <= $row["reorder_level"]) {
                        $status = "low_stock";
                    } else {
                        $status = "in_stock";
                    }

                ?>

                <tr class="product-row" data-status="<?php echo $status; ?>">

                    <td><?php echo $row["product_id"];?></td>
                    <td><?php echo $row["product_name"];?></td>
                    <td><?php echo $row["category"];?></td>
                    <td>₱<?php echo $row["cost"]; ?></td>
                    <td>₱<?php echo $row["price"];?></td>
                    <td><?php echo $row["stock_quantity"];?></td>
                    <td>
                        <?php
                            if ($status == "out_of_stock") {
                                echo '<span class="stock-badge out-of-stock">Out of Stock</span>';
                            } elseif ($status == "low_stock") {
                                echo '<span class="stock-badge low-stock">Low Stock</span>';
                            } else {
                                echo '<span class="stock-badge in-stock">In Stock</span>';

                            }
                        ?>
                    </td>
                    <td>
                        <button type="button"
                            class="btn btn-sm btn-outline-primary edit-product-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editProductModal"
                            data-id="<?php echo $row["product_id"]; ?>"
                            data-name="<?php echo htmlspecialchars($row["product_name"]); ?>"
                            data-category="<?php echo htmlspecialchars($row["category"]); ?>"
                            data-price="<?php echo $row["price"]; ?>"
                            data-cost="<?php echo $row["cost"]; ?>"
                            data-stock="<?php echo $row["stock_quantity"]; ?>"
                            data-reorder="<?php echo $row["reorder_level"]; ?>">
                            Edit
                        </button>

                        <button 
                            type = "button"
                            class="btn btn-sm btn-outline-danger delete-product-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteProductModal"
                            data-id="<?php echo $row["product_id"]; ?>"
                            data-name="<?php echo htmlspecialchars($row["product_name"]); ?>">
                            Delete
                        </button>
                    </td>
                </tr>

                <?php
                }
                ?>
            </tbody>

        </table>

    </div>

    </div>
    </main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>


<script>
//edit
document.addEventListener("DOMContentLoaded", function () {

    const editButtons = document.querySelectorAll(".edit-product-btn");

    editButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("editProductId").value =
                button.dataset.id;

            document.getElementById("editProductName").value =
                button.dataset.name;

            document.getElementById("editCategory").value =
                button.dataset.category;

            document.getElementById("editPrice").value =
                button.dataset.price;

            document.getElementById("edit_cost").value =
                button.dataset.cost;

            document.getElementById("editStock").value =
                button.dataset.stock;

            document.getElementById("editReorder").value =
                button.dataset.reorder;

        });

    });

});
</script>


<script>
//delete
document.addEventListener("DOMContentLoaded", function () {

    const deleteButtons = document.querySelectorAll(".delete-product-btn");

    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("deleteProductId").value =
                button.dataset.id;

            document.getElementById("deleteProductName").textContent =
                button.dataset.name;

        });

    });

});
</script>


<script>
//success notification
    document.addEventListener("DOMContentLoaded", function () {

    const toastElement = document.getElementById("successToast");

    if (toastElement) {

        const toast = new bootstrap.Toast(toastElement, {
            delay: 3000
        });

        toast.show();
    }

});
</script>

<script>
    //error notification
document.addEventListener("DOMContentLoaded", function () {

    const toastElement = document.getElementById("errorToast");

    if (toastElement) {

        const toast = new bootstrap.Toast(toastElement, {
            delay: 3000
        });

        toast.show();
    }

});
</script>



<script>
    //live ssearching
document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("searchProduct");
    const statusFilter = document.getElementById("statusFilter");
    const productRows = document.querySelectorAll(".product-row");


    function filterProducts() {

        const searchText = searchInput.value.toLowerCase().trim();
        const selectedStatus = statusFilter.value;


        productRows.forEach(function (row) {

            const productName =
                row.cells[1].textContent.toLowerCase();

            const productStatus =
                row.dataset.status;


            const matchesSearch =
                productName.includes(searchText);

            const matchesStatus =
                selectedStatus === "all" ||
                productStatus === selectedStatus;


            if (matchesSearch && matchesStatus) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }

        });

    }


    // LIVE SEARCH
    searchInput.addEventListener("input", function () {
        filterProducts();
    });


    // STATUS FILTER
    statusFilter.addEventListener("change", function () {
        filterProducts();
    });


    // RESET SEARCH WHEN CLICKING OUTSIDE
    searchInput.addEventListener("blur", function () {

        searchInput.value = "";

        filterProducts();

    });

});
</script>
</body>
</html>