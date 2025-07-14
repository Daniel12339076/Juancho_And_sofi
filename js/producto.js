document.addEventListener("DOMContentLoaded", () => {
  // Las funciones showAlert y addToCart ahora son globales desde main.js
  // No es necesario declararlas aquí de nuevo.

  // Inicializar el contador del carrito
  window.updateCartCounter() // Usa la función global

  // Manejar cambio de imagen principal
  const thumbnails = document.querySelectorAll(".image-thumbnails img")
  thumbnails.forEach((thumbnail) => {
    thumbnail.addEventListener("click", function () {
      window.changeMainImage(this.src)
      thumbnails.forEach((t) => t.classList.remove("active"))
      this.classList.add("active")
    })
  })

  // Manejar selección de talla
  const sizeOptions = document.querySelectorAll(".size-option")
  sizeOptions.forEach((option) => {
    option.addEventListener("click", function () {
      window.selectOption(this, "size-option")
    })
  })

  // Manejar selección de color
  const colorOptions = document.querySelectorAll(".color-option")
  colorOptions.forEach((option) => {
    option.addEventListener("click", function () {
      window.selectOption(this, "color-option")
    })
  })
})

function changeMainImage(src) {
  document.getElementById("main-product-image").src = src
}

function changeQuantity(delta) {
  const quantityInput = document.getElementById("quantity")
  let currentQty = Number.parseInt(quantityInput.value)
  const maxQty = Number.parseInt(quantityInput.max)

  currentQty += delta

  if (currentQty < 1) {
    currentQty = 1
  }
  if (currentQty > maxQty) {
    currentQty = maxQty
    window.showAlert(`Solo hay ${maxQty} unidades disponibles en stock.`, "warning") // Usa la función global
  }
  quantityInput.value = currentQty
}

function selectOption(element, className) {
  const parent = element.closest(`.${className}s`)
  if (parent) {
    parent.querySelectorAll(`.${className}`).forEach((opt) => opt.classList.remove("selected"))
  }
  element.classList.add("selected")
}

async function addToCartFromDetail(productId) {
  const quantityInput = document.getElementById("quantity")
  const quantity = Number.parseInt(quantityInput.value)

  // Validar que se haya seleccionado talla/color si existen opciones
  const sizeOptions = document.querySelectorAll(".size-option")
  const colorOptions = document.querySelectorAll(".color-option")

  if (sizeOptions.length > 0 && !document.querySelector(".size-option.selected")) {
    window.showAlert("Por favor, selecciona una talla.", "warning") // Usa la función global
    return
  }
  if (colorOptions.length > 0 && !document.querySelector(".color-option.selected")) {
    window.showAlert("Por favor, selecciona un color.", "warning") // Usa la función global
    return
  }

  // Llamar a la función global addToCart definida en main.js
  await window.addToCart(productId, quantity) // Usa la función global
}

function addToWishlist(productId) {
  // Lógica para añadir a favoritos
  window.showAlert(`Producto ${productId} añadido a favoritos (funcionalidad no implementada).`, "info") // Usa la función global
}
