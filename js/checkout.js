document.addEventListener("DOMContentLoaded", () => {
  const orderData = JSON.parse(sessionStorage.getItem("orderData"))
  const alertContainer = document.getElementById("alert-container") // Obtener el contenedor de alertas

  // Declare showAlert function
  function showAlert(message, type) {
    const alertElement = document.createElement("div")
    alertElement.className = `alert alert-${type}`
    alertElement.textContent = message
    alertContainer.appendChild(alertElement) // Añadir al contenedor específico
    setTimeout(() => {
      alertElement.remove() // Usar .remove() para eliminar el elemento
    }, 3000)
  }

  // Declare formatCurrency function
  function formatCurrency(amount) {
    return amount.toLocaleString("es-CO", { style: "currency", currency: "COP" })
  }

  if (!orderData || orderData.items.length === 0) {
    showAlert("No hay productos en el carrito para finalizar la compra.", "error")
    setTimeout(() => {
      window.location.href = "carrito.php"
    }, 2000)
    return
  }

  // Rellenar campos ocultos del formulario
  document.getElementById("items-data").value = JSON.stringify(orderData.items)
  document.getElementById("total-data").value = orderData.total

  // Mostrar resumen del pedido
  displayOrderSummary(orderData)

  // Lógica para tipo de entrega
  const tipoEntregaRadios = document.querySelectorAll('input[name="tipo_entrega"]')
  const direccionGroup = document.getElementById("direccion-group")
  const direccionInput = document.getElementById("direccion")
  const metodoPagoEfectivo = document.querySelector('input[name="metodo_pago"][value="efectivo"]')
  const metodoPagoTarjeta = document.querySelector('input[name="metodo_pago"][value="tarjeta"]')

  tipoEntregaRadios.forEach((radio) => {
    radio.addEventListener("change", (event) => {
      if (event.target.value === "online") {
        direccionGroup.style.display = "block"
        direccionInput.required = true
        // Si se selecciona envío a domicilio, deshabilitar efectivo y seleccionar tarjeta
        metodoPagoEfectivo.disabled = true
        if (metodoPagoEfectivo.checked) {
          metodoPagoTarjeta.checked = true
        }
      } else {
        // local
        direccionGroup.style.display = "none"
        direccionInput.required = false
        direccionInput.value = "" // Limpiar dirección si no es envío
        // Si se selecciona recogida local, habilitar efectivo
        metodoPagoEfectivo.disabled = false
      }
    })
  })

  // Inicializar el estado de la dirección y método de pago al cargar la página
  // Esto asegura que si 'online' está checked por defecto, la dirección sea requerida
  const initialTipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked').value
  if (initialTipoEntrega === "online") {
    direccionGroup.style.display = "block"
    direccionInput.required = true
    metodoPagoEfectivo.disabled = true
  } else {
    direccionGroup.style.display = "none"
    direccionInput.required = false
    metodoPagoEfectivo.disabled = false
  }

  // Manejar el envío del formulario
  document.getElementById("checkout-form").addEventListener("submit", async (event) => {
    event.preventDefault()
    const form = event.target
    const submitButton = form.querySelector('button[type="submit"]')

    submitButton.disabled = true
    submitButton.classList.add("loading")
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...'

    const formData = new FormData(form)
    const data = Object.fromEntries(formData.entries())
    data.items_data = document.getElementById("items-data").value
    data.total_data = document.getElementById("total-data").value

    try {
      const response = await fetch("checkout.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(data).toString(),
      })

      // Check if the response URL indicates a successful redirect to the confirmation page
      if (response.ok && response.url.includes("orden-confirmada.php")) {
        // If PHP successfully redirected, then navigate the browser to that URL
        window.location.href = response.url
      } else {
        // If not a successful redirect, then parse for potential server-side errors
        const responseText = await response.text()
        if (responseText.includes("alert-error")) {
          const parser = new DOMParser()
          const doc = parser.parseFromString(responseText, "text/html")
          const errorMessage = doc.querySelector(".alert-error")?.textContent.trim()
          showAlert(errorMessage || "Error desconocido al procesar el pedido.", "error")
        } else {
          // Fallback for unexpected responses that are not errors and not redirects
          // This might happen if PHP outputs something unexpected before redirecting,
          // or if the redirect itself fails silently on the server.
          showAlert("Error inesperado al procesar el pedido. Por favor, inténtalo de nuevo.", "error")
          console.error("Unexpected server response:", responseText)
        }
      }
    } catch (error) {
      console.error("Error en la solicitud de checkout:", error)
      showAlert("Error de conexión al procesar el pedido.", "error")
    } finally {
      submitButton.disabled = false
      submitButton.classList.remove("loading")
      submitButton.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Pedido'
    }
  })
})

function displayOrderSummary(orderData) {
  const orderItemsContainer = document.getElementById("order-items")
  orderItemsContainer.innerHTML = "" // Limpiar

  orderData.items.forEach((item) => {
    const itemDiv = document.createElement("div")
    itemDiv.className = "order-item"
    itemDiv.innerHTML = `
          <div class="order-item-image">
              <img src="images/productos/${item.imagen}" alt="${item.nombre}" onerror="this.src='/placeholder.svg?height=50&width=50'">
          </div>
          <div class="order-item-info">
              <div class="order-item-name">${item.nombre}</div>
              <div class="order-item-details">Cantidad: ${item.cantidad}</div>
          </div>
          <div class="order-item-price">${window.formatCurrency(item.precio_final * item.cantidad)}</div>
      `
    orderItemsContainer.appendChild(itemDiv)
  })

  document.getElementById("checkout-subtotal").textContent = window.formatCurrency(orderData.subtotal)
  document.getElementById("checkout-descuentos").textContent = `-${window.formatCurrency(orderData.descuentos)}`
  document.getElementById("checkout-total").textContent = window.formatCurrency(orderData.total)
}
