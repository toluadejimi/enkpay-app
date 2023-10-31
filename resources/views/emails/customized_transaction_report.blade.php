<!DOCTYPE html>
<html>
<head>

 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>


    <style>
        /* Add your CSS styles here for the PDF layout */
        body {
            font-family: Arial, sans-serif;
        }
        .header {
            text-align: center;
        }
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
        }
        .transaction-table th, .transaction-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .transaction-table th {
            background-color: #f2f2f2;
        }

         .ltext {
                  text-align: left;
        }

    </style>
</head>
<body>






    <div class="header">
        <img class="my-3" src="https://enkwave.com/wp-content/uploads/2023/03/logo.png" width="190" height="auto" />
        <h5 class="text-muted">Monthly Transaction Report</h5>
    </div>

  <div class="row header">

    <div class="col-6 mt-5">
      <ul class="ltext" style="list-style-type:none;">

        <li>Name: {{$name}}</li>
        <li>Date: {{$date}}</li>
        <li>Opening Balance: {{$date}}</li>
        <li>Closing Balance: {{$date}}</li>



      </ul>
    </div>


  <section>
    <div class="card my-5">
          <div class="card-body p-5">

    <table class="transaction-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->date }}</td>
                    <td>{{ $transaction->description }}</td>
                    <td>${{ number_format($transaction->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

             <div class="header">

        <p class="text-muted my-5">Thanks for choosing ENKPAY</p>
    </div>


      </div>
    </div>
  </section>
</body>
</html>
