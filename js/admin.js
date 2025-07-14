// Funciones globales para el panel de administración

// Función para mostrar alertas
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

// Función para cerrar modales (genérica)
function closeModal() {
  const modals = document.querySelectorAll(".modal")
  modals.forEach((modal) => {
    modal.style.display = "none"
  })
}

// Cerrar modal al hacer clic fuera (genérico)
window.onclick = (event) => {
  const modals = document.querySelectorAll(".modal")
  modals.forEach((modal) => {
    if (event.target === modal) {
      modal.style.display = "none"
    }
  })
}

// Previsualización de imagen para formularios (si aplica)
document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imagen")
  const imagePreview = document.getElementById("imagePreview")

  if (imageInput && imagePreview) {
    imageInput.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader()
        reader.onload = (e) => {
          imagePreview.innerHTML = `<img src="${e.target.result}" alt="Previsualización">`
        }
        reader.readAsDataURL(this.files[0])
      } else {
        imagePreview.innerHTML = ""
      }
    })
  }
})
