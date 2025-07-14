// Funcionalidad para gestión de pedidos

// Ver detalles del pedido
async function viewOrderDetails(orderId) {
  try {
    const response = await fetch(`../api/pedido-detalles.php?id=${orderId}`)
    const data = await response.json()

    if (data.error) {
      showAlert(data.error, "error")
      return
    }

    displayOrderModal(data)
  } catch (error) {
    console.error("Error:", error)
    showAlert("Error al cargar los detalles del pedido", "error")
  }
}

// Mostrar modal con detalles del pedido
function displayOrderModal(orderData) {
  const modal = document.getElementById("orderModal")
  const modalBody = document.getElementById("orderModalBody")

  const html = `
    <div class="order-detail-content">
        <div class="order-summary">
            <h4>Información del Pedido</h4>
            <div class="info-grid">
                <div class="info-item">
                    <label>Código:</label>
                    <span>${orderData.orden.codigo}</span>
                </div>
                <div class="info-item">
                    <label>Fecha:</label>
                    <span>${formatDate(orderData.orden.fecha)}</span>
                </div>
                <div class="info-item">
                    <label>Estado:</label>
                    <span class="status-badge status-${orderData.orden.estado.toLowerCase()}">${orderData.orden.estado}</span>
                </div>
                <div class="info-item">
                    <label>Tipo:</label>
                    <span>${orderData.orden.tipo_venta === "local" ? "Recogida en Tienda" : "Envío a Domicilio"}</span>
                </div>
                <div class="info-item">
                    <label>Total:</label>
                    <span class="total-price">$${Number(orderData.orden.total).toLocaleString()}</span>
                </div>
            </div>
        </div>
        
        <div class="customer-summary">
            <h4>Información del Cliente</h4>
            <div class="customer-details">
                <div class="customer-item">
                    <i class="fas fa-user"></i>
                    <div>
                        <strong>${orderData.cliente.nombre}</strong>
                        <small>${orderData.cliente.correo}</small>
                    </div>
                </div>
                <div class="customer-item">
                    <i class="fas fa-phone"></i>
                    <span>${orderData.cliente.celular}</span>
                </div>
            </div>
        </div>
        
        <div class="items-summary">
            <h4>Productos Pedidos</h4>
            <div class="items-list">
                ${orderData.items
                  .map(
                    (item) => `
                    <div class="item-row">
                        <div class="item-image">
                            <img src="../images/productos/${item.imagen}" alt="${item.nombre}" onerror="this.src='/placeholder.svg?height=50&width=50'">
                        </div>
                        <div class="item-info">
                            <div class="item-name">${item.nombre}</div>
                            <div class="item-details">Cantidad: ${item.cantidad}</div>
                        </div>
                        <div class="item-price">
                            $${Number(item.valor_total).toLocaleString()}
                        </div>
                    </div>
                `,
                  )
                  .join("")}
            </div>
        </div>
        
        <div class="order-actions-modal">
            <button onclick="printOrder(${orderData.orden.id})" class="btn btn-secondary">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button onclick="sendOrderEmail(${orderData.orden.id})" class="btn btn-info">
                <i class="fas fa-envelope"></i> Enviar Email
            </button>
            <button onclick="closeOrderModal()" class="btn btn-outline">
                Cerrar
            </button>
        </div>
    </div>
`

  modalBody.innerHTML = html
  modal.style.display = "block"
}

// Cerrar modal
function closeOrderModal() {
  const modal = document.getElementById("orderModal")
  modal.style.display = "none"
}

// Imprimir pedido
function printOrder(orderId) {
  const printWindow = window.open(`../print-order.php?id=${orderId}`, "_blank")
  printWindow.focus()
}

// Enviar email del pedido
async function sendOrderEmail(orderId) {
  try {
    const response = await fetch("../api/send-order-email.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ orderId: orderId }),
    })

    const result = await response.json()

    if (result.success) {
      showAlert("Email enviado exitosamente", "success")
    } else {
      showAlert(result.error || "Error al enviar email", "error")
    }
  } catch (error) {
    console.error("Error:", error)
    showAlert("Error al enviar email", "error")
  }
}

// Exportar pedidos
function exportOrders() {
  const params = new URLSearchParams(window.location.search)
  const exportUrl = `../api/export-orders.php?${params.toString()}`
  window.open(exportUrl, "_blank")
}

// Formatear fecha
function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString("es-ES", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  })
}

// Actualizar estado en tiempo real
function updateOrderStatus(orderId, newStatus) {
  if (confirm(`¿Confirmas cambiar el estado a "${newStatus}"?`)) {
    const form = document.createElement("form")
    form.method = "POST"
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="orden_id" value="${orderId}">
        <input type="hidden" name="nuevo_estado" value="${newStatus}">
    `
    document.body.appendChild(form)
    form.submit()
  }
}

// Mostrar alertas
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

// Event listeners
document.addEventListener("DOMContentLoaded", () => {
  // Cerrar modal al hacer clic fuera
  window.onclick = (event) => {
    const modal = document.getElementById("orderModal")
    if (event.target === modal) {
      closeOrderModal()
    }
  }

  // Auto-refresh cada 30 segundos
  setInterval(() => {
    if (
      !document.getElementById("orderModal").style.display ||
      document.getElementById("orderModal").style.display === "none"
    ) {
      location.reload()
    }
  }, 30000)
})

// Exportar funciones globales
window.viewOrderDetails = viewOrderDetails
window.closeOrderModal = closeOrderModal
window.printOrder = printOrder
window.sendOrderEmail = sendOrderEmail
window.exportOrders = exportOrders
window.updateOrderStatus = updateOrderStatus
