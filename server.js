const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');
const sequelize = require('./config/database');
const cron = require('node-cron');

// Load environment variables
dotenv.config();

const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files
app.use(express.static('public'));

// Import routes
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/users');
const investmentRoutes = require('./routes/investments');
const transactionRoutes = require('./routes/transactions');
const adminRoutes = require('./routes/admin');
const agentRoutes = require('./routes/agent');

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/investments', investmentRoutes);
app.use('/api/transactions', transactionRoutes);
app.use('/api/admin', adminRoutes);
app.use('/api/agent', agentRoutes);

// Serve frontend
app.get('/', (req, res) => {
  res.sendFile(__dirname + '/public/index.html');
});

// Import cron jobs
const { calculateDailyProfits } = require('./cron/dailyProfit');

// Schedule daily profit calculation (runs at midnight)
cron.schedule('0 0 * * *', async () => {
  console.log('Running daily profit calculation...');
  await calculateDailyProfits();
});

// Database connection and server start
const PORT = process.env.PORT || 5000;

sequelize.authenticate()
  .then(() => {
    console.log('Database connected successfully.');
    return sequelize.sync({ alter: true });
  })
  .then(() => {
    console.log('Database synchronized.');
    app.listen(PORT, () => {
      console.log(`Server running on port ${PORT}`);
    });
  })
  .catch(err => {
    console.error('Unable to connect to the database:', err);
  });

module.exports = app;

