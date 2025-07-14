// Funciones globales para el frontend de la tienda

document.addEventListener("DOMContentLoaded", () => {
  // Toggle para la barra de búsqueda
  const searchToggle = document.querySelector(".search-toggle")
  const searchBar = document.querySelector(".search-bar")
  if (searchToggle && searchBar) {
    searchToggle.addEventListener("click", (e) => {
      e.preventDefault()
      searchBar.classList.toggle("active")
      if (searchBar.classList.contains("active")) {
        document.getElementById("searchInput").focus()
      }
    })
  }

  // Funcionalidad de búsqueda
  const searchInput = document.getElementById("searchInput")
  const searchButton = document.getElementById("searchButton")

  if (searchButton && searchInput) {
    searchButton.addEventListener("click", () => {
      performSearch(searchInput.value)
    })

    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        performSearch(searchInput.value)
      }
    })
  }

  // Actualizar contador del carrito al cargar la página
  updateCartCounter()
})

function performSearch(query) {
  if (query.trim()) {
    window.location.href = `productos.php?buscar=${encodeURIComponent(query)}`
  } else {
    showAlert("Por favor, ingresa un término de búsqueda.", "info")
  }
}

// Función para mostrar alertas flotantes
function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type}`
  alertDiv.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)}"></i>
        <span>${message}</span>
    `

  alertDiv.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 10001;
        min-width: 300px;
        max-width: 500px;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transform: translateX(100%);
        transition: transform 0.3s ease-out;
    `

  document.body.appendChild(alertDiv)

  setTimeout(() => {
    alertDiv.style.transform = "translateX(0)"
  }, 100)

  setTimeout(() => {
    alertDiv.style.transform = "translateX(100%)"
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.parentNode.removeChild(alertDiv)
      }
    }, 300)
  }, 4000)
}

function getAlertIcon(type) {
  const icons = {
    success: "check-circle",
    error: "exclamation-circle",
    warning: "exclamation-triangle",
    info: "info-circle",
  }
  return icons[type] || "info-circle"
}

// Función para obtener el carrito desde localStorage
function getCart() {
  const cart = localStorage.getItem("carrito")
  return cart ? JSON.parse(cart) : {} // Retorna un objeto (mapa)
}

// Función para guardar el carrito en localStorage
function saveCart(cart) {
  localStorage.setItem("carrito", JSON.stringify(cart))
  updateCartCounter()
}

// Función para actualizar el contador del carrito en el header
function updateCartCounter() {
  const cart = getCart()
  // Suma las cantidades de todos los items en el objeto del carrito
  const totalItems = Object.values(cart).reduce((sum, item) => sum + item.cantidad, 0)
  const cartBadge = document.querySelector(".cart-badge")
  if (cartBadge) {
    cartBadge.textContent = totalItems
    cartBadge.style.display = totalItems > 0 ? "flex" : "none"
  }
}

// Función para agregar un producto al carrito (desde cualquier parte de la tienda)
async function addToCart(productId, quantity = 1) {
  try {
    const response = await fetch("api/carrito.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ action: "add", productId, quantity }),
    })
    const result = await response.json()

    if (result.success) {
      saveCart(result.carrito) // Guardar el carrito actualizado en localStorage
      showAlert(result.message, "success")
    } else {
      showAlert(result.message, "error")
    }
  } catch (error) {
    console.error("Error al añadir al carrito:", error)
    showAlert("Error al conectar con el servidor para añadir al carrito.", "error")
  }
}

// Función para formatear números a moneda (COP)
function formatCurrency(amount) {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}
