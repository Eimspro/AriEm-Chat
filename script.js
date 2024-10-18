//Emilio Madriz 16-10-2024
// This script provides basic functionality for viewing and managing chats.
let messagesData = [];

// Obtener el valor del nombre del usuario desde el input oculto
const userName = document.getElementById('nameuser').value;

// Función para obtener los datos de chat desde PHP usando fetch
async function loadChatData() {
    try {
        const response = await fetch('Chats/load.php');

        if (!response.ok) {
            throw new Error('Error en la solicitud al servidor');
        }

        const data = await response.json();

        if (data.status === 'success') {
            messagesData = data.data;
            displayContacts(messagesData); // Muestra la lista de contactos
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error en la solicitud: ' + error.message);
    }
}

// Función para mostrar la lista de contactos sin repetir
function displayContacts(messages) {
    const contactList = document.getElementById('contactList');
    contactList.innerHTML = ''; // Limpiar la lista

    const uniqueContacts = {};

    // Agrupar mensajes por contacto y evitar duplicados
    messages.forEach(message => {
        // Generar un identificador único para cada par de números
        const contactKey = [message.phone_send, message.phone_receive].sort().join('-');

        if (!uniqueContacts[contactKey]) {
            uniqueContacts[contactKey] = {
                name_send: message.name_send,
                name_receive: message.name_receive,
                messages: [],
                phone_send: message.phone_send,
                phone_receive: message.phone_receive // Guardar ambos números
            };
        }

        // Agregar los mensajes al contacto correspondiente
        uniqueContacts[contactKey].messages.push(message);
    });

    // Crear la lista de contactos
    Object.values(uniqueContacts).forEach(contactInfo => {
        const contactDiv = document.createElement('div');
        contactDiv.classList.add('contact');
        contactDiv.innerHTML = `
            <img src="https://via.placeholder.com/50" alt="Avatar"> 
            <div class="contact-name">${contactInfo.name_send === userName ? contactInfo.name_receive : contactInfo.name_send}</div>
        `;

        // Agregar evento de clic para mostrar los mensajes de ese contacto
        contactDiv.addEventListener('click', () => showMessages(contactInfo.messages, contactInfo));

        // Agregar el contacto a la lista
        contactList.appendChild(contactDiv);
    });
}

// Función para mostrar los mensajes de un contacto
function showMessages(contactMessages, contactInfo) {
    const chatPanel = document.getElementById('chatPanel');
    const chatMessages = document.getElementById('chatMessages');
    const contactNameElement = document.getElementById('contactName');
    const contactPhoneElement = document.getElementById('contactPhone'); // Elemento para mostrar el número

    // Mostrar el nombre del contacto en el encabezado
    const contactName = (contactInfo.name_send === userName) ? contactInfo.name_receive : contactInfo.name_send;
    contactNameElement.textContent = contactName; // Cambiar a mostrar el nombre del contacto

    // Mostrar el número de teléfono del contacto correcto
    const contactPhone = (contactInfo.name_send === userName) ? contactInfo.phone_receive : contactInfo.phone_send;
    contactPhoneElement.textContent = contactPhone; // Asumimos que phone es el número a mostrar

    // Limpiar el panel de mensajes
    chatMessages.innerHTML = '';

    // Mostrar los mensajes en el panel de chat
    contactMessages.forEach(message => {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', message.phone_send === userName ? 'sent' : 'received');
        messageDiv.innerHTML = `                
            <strong>${message.phone_send === userName ? 'Tu' : message.name_send}:</strong> ${message.message}
        `;
        chatMessages.appendChild(messageDiv);
    });

    // Mostrar el panel de chat
    chatPanel.style.display = 'flex';
    chatMessages.scrollTop = chatMessages.scrollHeight; // Desplazar al último mensaje
}

// Cargar los datos del chat al inicio
loadChatData();

function Send() {
    const messageBody = document.getElementById('body').value.trim(); // Obtener el contenido del input
    const contactPhone = document.getElementById('contactPhone').textContent; // Obtener el número de teléfono del contacto

    if (messageBody !== '') {
        // Asignar los valores al formulario oculto
        document.getElementById('hiddenPhone').value = contactPhone;
        document.getElementById('hiddenMessage').value = messageBody;

        // Enviar el formulario oculto
        document.getElementById('hiddenForm').submit();
    } else {
        alert('Por favor, escribe un mensaje antes de enviar.');
    }
}
