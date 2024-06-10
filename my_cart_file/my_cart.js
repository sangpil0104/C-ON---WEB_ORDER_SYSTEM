document.addEventListener('DOMContentLoaded', function() {
  const deleteButtons = document.querySelectorAll('.delete-button');
  deleteButtons.forEach(button => {
      button.addEventListener('click', function() {
          const itemNo = this.getAttribute('data-itemno');
          const cartId = this.getAttribute('data-cartid');
          deleteCartItem(itemNo, cartId);
      });
  });

  document.getElementById('checkoutButton').addEventListener('click', function() {
      checkout();
  });
});

function deleteCartItem(itemNo, cartId) {
  fetch('delete_cart_item.php', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `itemno=${encodeURIComponent(itemNo)}&cartid=${encodeURIComponent(cartId)}`
  })
  .then(response => response.json())
  .then(data => {
      if (data.success) {
          alert('Item deleted successfully.');
          location.reload();
      } else {
          alert('Error deleting item: ' + data.error);
      }
  })
  .catch(error => console.error('Error:', error));
}

function checkout() {
  fetch('checkout.php', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: ''
  })
  .then(response => response.json())
  .then(data => {
      if (data.success) {
          alert('Checkout successful.');
          location.reload();
      } else {
          alert('Error during checkout: ' + data.error);
      }
  })
  .catch(error => console.error('Error:', error));
}
