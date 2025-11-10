const express = require('express');
const router = express.Router();
const { authenticate } = require('../middleware/auth');
const { User, Investment, Transaction, Referral } = require('../models');
const { Op } = require('sequelize');

// Get user profile
router.get('/profile', authenticate, async (req, res) => {
  try {
    const user = await User.findByPk(req.user.id, {
      attributes: { exclude: ['password'] },
      include: [
        {
          model: Investment,
          as: 'investments',
          include: ['plan']
        }
      ]
    });

    res.json({ user });
  } catch (error) {
    console.error('Get profile error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get user dashboard data
router.get('/dashboard', authenticate, async (req, res) => {
  try {
    const userId = req.user.id;

    // Get user with balance
    const user = await User.findByPk(userId, {
      attributes: ['id', 'phone_number', 'role', 'referral_code', 'balance', 'created_at']
    });

    // Get active investments
    const activeInvestments = await Investment.findAll({
      where: {
        user_id: userId,
        status: 'active'
      },
      include: ['plan']
    });

    // Get completed investments
    const completedInvestments = await Investment.findAll({
      where: {
        user_id: userId,
        status: 'completed'
      },
      include: ['plan']
    });

    // Calculate total invested
    const totalInvested = await Investment.sum('amount', {
      where: { user_id: userId }
    }) || 0;

    // Calculate total profit
    const totalProfit = await Investment.sum('profit', {
      where: { user_id: userId }
    }) || 0;

    // Get referral stats
    const referralCount = await Referral.count({
      where: { inviter_id: userId }
    });

    const referralEarnings = await Referral.sum('bonus_amount', {
      where: { inviter_id: userId }
    }) || 0;

    // Get recent transactions
    const recentTransactions = await Transaction.findAll({
      where: { user_id: userId },
      order: [['date', 'DESC']],
      limit: 10
    });

    res.json({
      user,
      stats: {
        totalInvested: parseFloat(totalInvested),
        totalProfit: parseFloat(totalProfit),
        referralCount,
        referralEarnings: parseFloat(referralEarnings),
        activeInvestmentsCount: activeInvestments.length,
        completedInvestmentsCount: completedInvestments.length
      },
      activeInvestments,
      completedInvestments,
      recentTransactions
    });
  } catch (error) {
    console.error('Dashboard error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get referral link
router.get('/referral-link', authenticate, async (req, res) => {
  try {
    const user = await User.findByPk(req.user.id);
    const referralLink = `${req.protocol}://${req.get('host')}/register?ref=${user.referral_code}`;
    
    res.json({
      referral_code: user.referral_code,
      referral_link: referralLink
    });
  } catch (error) {
    console.error('Referral link error:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;

