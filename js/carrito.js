document.addEventListener("DOMContentLoaded", () => {
  loadCartItems()
  document.getElementById("checkout-btn").addEventListener("click", proceedToCheckout)
})

async function loadCartItems() {
  const cartItemsContainer = document.getElementById("cart-items")
  const emptyCartMessage = cartItemsContainer.querySelector(".empty-cart-message")

  try {
    const response = await fetch("api/carrito.php", { method: "GET" })
    const result = await response.json()

    const cartArray = Object.values(result.carrito || {})

    if (result.success && cartArray.length > 0) {
      emptyCartMessage.style.display = "none"
      cartItemsContainer.innerHTML = ""
      cartArray.forEach((item) => {
        const itemElement = createCartItemElement(item)
        cartItemsContainer.appendChild(itemElement)
      })
      updateCartSummary(result.totales) // <<< usamos los totales del servidor
      document.getElementById("checkout-btn").disabled = false
    } else {
      cartItemsContainer.innerHTML = ""
      emptyCartMessage.style.display = "block"
      updateCartSummary({ subtotal: 0, descuentos: 0, total: 0, total_items: 0 })
      document.getElementById("checkout-btn").disabled = true
    }
  } catch (error) {
    console.error("Error al cargar el carrito:", error)
    showAlert("Error al cargar los productos del carrito.", "error")
    cartItemsContainer.innerHTML = ""
    emptyCartMessage.style.display = "block"
    updateCartSummary({ subtotal: 0, descuentos: 0, total: 0, total_items: 0 })
    document.getElementById("checkout-btn").disabled = true
  }
}

function createCartItemElement(item) {
  const itemDiv = document.createElement("div")
  itemDiv.className = "cart-item"
  itemDiv.dataset.productId = item.id

  const originalPriceHtml =
    item.descuento > 0 ? `<span class="price original">${formatCurrency(item.precio_unitario)}</span>` : ""

  itemDiv.innerHTML = `
        <div class="item-image">
            <img src="images/productos/${item.imagen}" alt="${item.nombre}" onerror="this.src='/placeholder.svg?height=100&width=100'">
        </div>
        <div class="item-details">
            <h3>${item.nombre}</h3>
            <p>Precio: ${originalPriceHtml}<span class="price">${formatCurrency(item.precio_final)}</span></p>
            <p>Stock disponible: ${item.stock_disponible}</p>
        </div>
        <div class="item-quantity-controls">
            <button class="decrease-qty" data-id="${item.id}">-</button>
            <input type="number" name="cantidad" value="${item.cantidad}" min="1" max="${item.stock_disponible}" data-id="${item.id}" class="item-qty-input">
            <button class="increase-qty" data-id="${item.id}">+</button>
        </div>
        <button class="item-remove" data-id="${item.id}">
            <i class="fas fa-trash-alt"></i>
        </button>
    `

  const input = itemDiv.querySelector(".item-qty-input")

  itemDiv.querySelector(".decrease-qty").addEventListener("click", () => {
    let newQty = parseInt(input.value) - 1
    updateQuantity(item.id, newQty)
  })

  itemDiv.querySelector(".increase-qty").addEventListener("click", () => {
    let newQty = parseInt(input.value) + 1
    updateQuantity(item.id, newQty)
  })

  input.addEventListener("change", (e) => updateQuantity(item.id, Number.parseInt(e.target.value)))
  itemDiv.querySelector(".item-remove").addEventListener("click", () => removeItem(item.id))

  return itemDiv
}

// Definición de la función
async function updateQuantity(productId, newQuantity) {
  if (newQuantity < 1) {
    removeItem(productId);
    return;
  }

  try {
    const response = await fetch("api/carrito.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "update", productId, quantity: newQuantity }),
    });

    const result = await response.json();

    if (result.success) {
      saveCart(result.carrito);
      updateCartSummary(result.totales);
      loadCartItems();
    } else {
      showAlert(result.message, "error");
      loadCartItems();
    }
  } catch (error) {
    console.error("Error al actualizar cantidad:", error);
    showAlert("Error al conectar con el servidor para actualizar cantidad.", "error");
  }
}

// 🔑 Aquí la haces global (fuera de la función, no dentro del fetch)
window.updateQuantity = updateQuantity;


async function removeItem(productId) {
  if (!confirm("¿Estás seguro de que quieres eliminar este producto del carrito?")) {
    return
  }

  try {
    const response = await fetch("api/carrito.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "remove", productId }),
    })
    const result = await response.json()

    if (result.success) {
      saveCart(result.carrito)
      showAlert(result.message, "info")
      updateCartSummary(result.totales) // <<< usamos respuesta del servidor
      loadCartItems()
    } else {
      showAlert(result.message, "error")
    }
  } catch (error) {
    console.error("Error al eliminar producto:", error)
    showAlert("Error al conectar con el servidor para eliminar producto.", "error")
  }
}

// AHORA updateCartSummary SOLO PINTA
function updateCartSummary(totales) {
  document.getElementById("total-items-summary").textContent = totales.total_items
  document.getElementById("cart-subtotal").textContent = formatCurrency(totales.subtotal)
  document.getElementById("cart-descuentos").textContent = `-${formatCurrency(totales.descuentos)}`
  document.getElementById("cart-total").textContent = formatCurrency(totales.total)

  document.getElementById("checkout-btn").disabled = totales.total_items === 0
}

function proceedToCheckout() {
  const cart = getCart()
  if (Object.keys(cart).length === 0) {
    showAlert("Tu carrito está vacío. Agrega productos antes de proceder al pago.", "warning")
    return
  }

  sessionStorage.setItem(
    "orderData",
    JSON.stringify({
      items: Object.values(cart),
      subtotal: Number.parseFloat(document.getElementById("cart-subtotal").textContent.replace(/[^0-9,-]+/g, "").replace(",", ".")),
      descuentos: Number.parseFloat(document.getElementById("cart-descuentos").textContent.replace(/[^0-9,-]+/g, "").replace(",", ".")),
      total: Number.parseFloat(document.getElementById("cart-total").textContent.replace(/[^0-9,-]+/g, "").replace(",", ".")),
    }),
  )

  window.location.href = "checkout.php"
}

function showAlert(message, type) {
  console.log(`Alert: ${message} (Type: ${type})`)
}

function formatCurrency(amount) {
  return amount.toLocaleString("es-CO", { style: "currency", currency: "COP" })
}

function saveCart(cart) {
  localStorage.setItem("cart", JSON.stringify(cart))
  updateCartCounter(cart)
}

function getCart() {
  const cartJson = localStorage.getItem("cart")
  return cartJson ? JSON.parse(cartJson) : {}
}

function updateCartCounter(cart) {
  const cartCounter = document.getElementById("cart-counter")
  cartCounter.textContent = Object.keys(cart).length
}
