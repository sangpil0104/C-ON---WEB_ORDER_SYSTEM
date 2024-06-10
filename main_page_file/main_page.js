document.addEventListener('DOMContentLoaded', function() {
    const categories = document.querySelectorAll('.category');
    const menuItemsContainer = document.querySelector('.menu-items');

    categories.forEach(category => {
        category.addEventListener('click', function() {
            categories.forEach(cat => cat.classList.remove('active'));
            this.classList.add('active');
            const category = this.getAttribute('data-category');
            loadMenuItems(category);
        });
    });

    function loadMenuItems(category) {
        console.log(`Loading menu items for category: ${category}`);
        fetch('get_menu_items.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `category=${encodeURIComponent(category)}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Received data:', data);
            menuItemsContainer.innerHTML = '';

            if (data.error) {
                console.error(data.error);
            } else {
                data.forEach(item => {
                    const button = document.createElement('button');
                    button.className = 'menu-item';
                    button.textContent = item;
                    button.addEventListener('click', function() {
                        viewMenuItem(item);
                    });
                    menuItemsContainer.appendChild(button);
                });
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function viewMenuItem(item) {
        console.log(`Viewing item: ${item}`);
        window.location.href = `../menu_info_file/menu_info.php?item=${encodeURIComponent(item)}`;
    }
});
