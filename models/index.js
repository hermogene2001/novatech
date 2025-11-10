const User = require('./User');
const InvestmentPlan = require('./InvestmentPlan');
const Investment = require('./Investment');
const Transaction = require('./Transaction');
const Referral = require('./Referral');

// Define associations
Investment.belongsTo(User, { foreignKey: 'user_id', as: 'user' });
Investment.belongsTo(InvestmentPlan, { foreignKey: 'plan_id', as: 'plan' });
Transaction.belongsTo(User, { foreignKey: 'user_id', as: 'user' });
Referral.belongsTo(User, { foreignKey: 'inviter_id', as: 'inviter' });
Referral.belongsTo(User, { foreignKey: 'invitee_id', as: 'invitee' });

User.hasMany(Investment, { foreignKey: 'user_id', as: 'investments' });
User.hasMany(Transaction, { foreignKey: 'user_id', as: 'transactions' });
User.hasMany(Referral, { foreignKey: 'inviter_id', as: 'referralsGiven' });
User.hasMany(Referral, { foreignKey: 'invitee_id', as: 'referralsReceived' });

module.exports = {
  User,
  InvestmentPlan,
  Investment,
  Transaction,
  Referral
};

