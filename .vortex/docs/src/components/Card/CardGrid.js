import React from 'react';
import styles from './Card.module.css';

function CardGrid({ children, className = '' }) {
  return (
    <div className={`${styles['cards-grid']} ${className}`}>{children}</div>
  );
}

export default CardGrid;
