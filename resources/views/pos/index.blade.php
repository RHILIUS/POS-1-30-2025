@extends('layouts.master')

@section('title', 'Point of Sale')
@section('page-title', 'Point of Sale')

@section('content')
<div class="container mt-5">
  <div class="row">

    <!-- Product List Section -->
    <div class="col-md-8">
      <div class="card my-2">
        <div class="card-header">
          <h5>Product List</h5>
        </div>
        <div class="card-body">

          <form method="GET" action="{{ route('pos.index') }}" class="mb-3">
            <input type="text" name="search" class="form-control" placeholder="Search products.."
              value="{{ $search ?? '' }}">
          </form>
          <div style="overflow-x:auto;">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th></th>
                  <th>SKU</th>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Quantity</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @forelse($products as $product)
          <tr>
            <td>
            @if ($product->image_url)
        <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" width="50"
          height="50">
      @else
    <p>No image</p>
  @endif
            </td>
            <td>{{ $product->sku }}</td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->category->name }}</td>
            <td>{{ $product->price }}</td>
            <td>{{ $product->quantity }}</td>
            <td>
            <input type="number" class="form-control" min="1" value="1"
              id="quantity-{{ $product->product_id }}">
            <button class="btn btn-primary mt-2 add-to-cart" data-id="{{ $product->product_id }}">Add to
              Cart</button>
            </td>
          </tr>
        @empty
      <tr>
        <td colspan="6" class="text-center">No products available.</td>
      </tr>
    @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- Pagination Links -->
      <div> {{ $products->appends(['search' => $search,])->links('pagination::bootstrap-5')}}
      </div>
    </div>


    <!-- Cart Section -->
    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <h5>Cart</h5>
        </div>
        <div class="card-body">
          <div style="overflow-x:auto;">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($cart as $productId => $item)
          <tr>
            <td>{{ $item['name'] }}</td>
            <td>{{ $item['quantity'] }}</td>
            <td>{{ $item['subtotal'] }}</td>
            <td>
            <form action="{{ route('pos.removeFromCart') }}" method="POST">
              @csrf
              <input type="hidden" name="product_id" value="{{ $productId }}">
              <button type="submit" class="btn btn-danger">Remove</button>
            </form>
            </td>
          </tr>
        @endforeach
              </tbody>
            </table>
          </div>


          <!-- Checkout Section -->
          <div class="mt-3">
            <h4>SubTotal: {{ array_sum(array_column($cart, 'subtotal')) }}</h4>

            <!-- Tax and Discount Fields -->
            <div class="form-group">
              <label for="tax">Tax (%)</label>
              <input type="number" id="tax" class="form-control" min="0" value="{{ $settings->tax }}" readonly>
            </div>

            <div class="form-group">
              <label for="discount">Discount (%)</label>
              <input type="number" id="discount" class="form-control" min="0" value="{{ $settings->discount }}"
                readonly>
            </div>

            <div class="card-body">
              <!-- Customer Dropdown -->
              <div class="form-group mb-3">
                <label for="customer_id">Select Customer</label>
                <select id="customer_id" class="form-control" name="customer_id">
                  <option value="">Select a customer</option>
                  @foreach($customers as $customer)
            <option value="{{ $customer->customer_id }}">{{ $customer->name }}</option>
          @endforeach
                </select>
              </div>

              <div class="form-group">
                <label for="charge">Customer Payment</label>
                <input type="number" id="charge" class="form-control" min="0" placeholder="Enter amount received">
              </div>

              <div class="form-group">
                <label for="payment_mode">Payment Method</label>
                <select id="payment_mode" class="form-control">
                  <option value="cash">Cash</option>
                  <option value="card">Card</option>
                  <option value="mobile payment">Mobile Payment</option>
                </select>
              </div>
              <!-- Checkout Button -->
              <button class="btn btn-success btn-block mt-3" data-bs-toggle="modal"
                data-bs-target="#orderPreviewModal">Checkout</button>
            </div>
          </div>
        </div>
      </div>
    </div>


  </div>

  <!-- Include the Modal -->
  @include('pos.preview')

  <!-- Scripts for Cart and Checkout -->
  <script>
    // Add to Cart Functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
      button.addEventListener('click', function () {
        const productId = this.dataset.id;
        const quantity = document.querySelector(`#quantity-${productId}`).value;

        fetch('/pos/add-to-cart', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ product_id: productId, quantity: quantity })
        }).then(response => response.json())
          .then(data => {
            if (data.success) {
              alert(data.success);
              location.reload();
            } else {
              alert(data.error);
            }
          });
      });
    });
    // Checkout Functionality
    document.getElementById('checkout-btn').addEventListener('click', function () {
      const tax = document.getElementById('tax').value;
      const discount = document.getElementById('discount').value;
      const charge = document.getElementById('charge').value;
      const paymentMode = document.getElementById('payment_mode').value;
      const customerId = document.getElementById('customer_id').value;

      fetch('/pos/checkout', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          tax: tax,
          discount: discount,
          charge: charge,
          payment_mode: paymentMode,
          customer_id: customerId
        })
      }).then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.success);
            location.reload();  // Refresh after successful checkout
          } else {
            alert(data.error);
          }
        });
    });
  </script>
  @endsection